import pandas as pd
import fdb

# ==========================================
# CONFIGURACIÓN DE BASE DE DATOS
# ==========================================
DB_HOST = '172.24.10.251'
DB_PATH = 'C:/SisDLL20/BD/DB_SIDIST.FDB'
DB_USER = 'SYSDBA'
DB_PASS = '290990'

ARCHIVO_EXCEL = 'OPE032026DIST.xls'

def actualizar_dist_mercantil():
    print(f"Leyendo archivo Excel: {ARCHIVO_EXCEL}...")
    try:
        # header=7 le indica a Pandas que la fila 8 de Excel contiene los títulos.
        df = pd.read_excel(ARCHIVO_EXCEL, header=7)
    except Exception as e:
        print(f"Error al leer el archivo Excel: {e}")
        return

    print("Conectando a la base de datos Firebird...")
    try:
        con = fdb.connect(host=DB_HOST, database=DB_PATH, user=DB_USER, password=DB_PASS, charset='UTF8')
        cur = con.cursor()
    except Exception as e:
        print(f"Error de conexión a Firebird: {e}")
        return

    exitosos = []
    fallidos = []
    ignorados = 0
    total_identificadas = 0

    # Nos aseguramos de leer siempre la primera columna (Columna A)
    col_lecheria = df.columns[0]

    # NOTA: Cambia "LECHERIA" si tu tabla se llama distinto
    update_query = """
        UPDATE LECHERIA
        SET TIPO_PUNTO_VENTA = 2 
        WHERE LECHER = ?
    """

    print("Procesando y actualizando a Distribución Mercantil (Valor: 2)...")
    for index, row in df.iterrows():
        
        lecheria_raw = row[col_lecheria]
        
        if pd.isna(lecheria_raw):
            continue

        try:
            # Si la fila dice "DIST. MERC." o algo que no sea número, caerá aquí y se ignorará
            lecheria_limpia = int(float(lecheria_raw))
        except ValueError:
            ignorados += 1
            continue

        total_identificadas += 1

        # --- ACTUALIZAR ---
        try:
            cur.execute(update_query, (lecheria_limpia,))
            con.commit()
            exitosos.append(lecheria_limpia)
        except Exception as e:
            con.rollback()
            error_msg = str(e).split('\n')[0]
            fallidos.append({'lecheria': lecheria_limpia, 'error': error_msg})

    cur.close()
    con.close()

    # ==========================================
    # REPORTE FINAL LIMPIO
    # ==========================================
    print("\n==========================================")
    print("        REPORTE DE ACTUALIZACIÓN          ")
    print("==========================================")
    print(f"📊 Total de lecherías válidas en el reporte: {total_identificadas}")
    print(f"✅ Se actualizaron exitosamente a Dist. Mercantil (2): {len(exitosos)}")
    print(f"👻 Se ignoraron (Subtítulos como 'DIST. MERC.' o vacías): {ignorados}")
    
    if fallidos:
        print(f"\n❌ Fallaron {len(fallidos)} lecherías:")
        for f in fallidos:
            print(f"-> Lechería No. {f['lecheria']} | Motivo: {f['error']}")
    else:
        print("\n🎉 ¡Cero errores! Todas las lecherías procesadas fueron actualizadas.")

if __name__ == '__main__':
    actualizar_dist_mercantil()

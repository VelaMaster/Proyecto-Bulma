import pandas as pd
import fdb

# ==========================================
# CONFIGURACIÓN DE BASE DE DATOS
# ==========================================
DB_HOST = '172.24.10.251'
DB_PATH = 'C:/SisDLL20/BD/DB_SIDIST.FDB'
DB_USER = 'SYSDBA'
DB_PASS = '290990'

ARCHIVO_EXCEL = 'OPE032026DICONSA.xls'

def actualizar_tipo_venta():
    print(f"Leyendo archivo Excel: {ARCHIVO_EXCEL}...")
    try:
        # header=5 le dice a Pandas que la fila 6 de Excel (índice 5) contiene los títulos
        df = pd.read_excel(ARCHIVO_EXCEL, header=5)
        
        # Limpiamos los nombres por si acaso, cambiando saltos de línea por espacios
        df.columns = df.columns.astype(str).str.strip().str.replace('\n', ' ')
        print(f"Columnas detectadas: {list(df.columns)[:5]} ...") # Imprime las primeras 5 para debugear
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

    # Usamos la posición en lugar del nombre exacto
    col_lecheria = df.columns[0] # Columna A
    col_precio = df.columns[1]   # Columna B

    # NOTA: Cambia "LECHERIA" por el nombre real de tu tabla maestra si es diferente
    update_query = """
        UPDATE LECHERIA
        SET TIPO_PUNTO_VENTA = ? 
        WHERE LECHER = ?
    """

    print("Procesando y actualizando el tipo de venta en la base de datos...")
    for index, row in df.iterrows():
        
        # Extraemos por la posición de la columna
        lecheria_raw = row[col_lecheria]
        
        if pd.isna(lecheria_raw):
            continue

        try:
            # Si es un texto como "CHALCATONGO" o "-", fallará aquí y se ignorará
            lecheria_limpia = int(float(lecheria_raw))
        except ValueError:
            ignorados += 1
            continue

        # Extraemos el precio
        precio_raw = row[col_precio]
        
        if pd.isna(precio_raw):
            ignorados += 1
            continue

        try:
            precio = float(precio_raw)
        except ValueError:
            ignorados += 1
            continue

        # --- MAPEO DE PRECIO A TIPO_PUNTO_VENTA ---
        tipo_punto_venta = None
        if precio == 4.5:
            tipo_punto_venta = 0
        elif precio == 6.5:
            tipo_punto_venta = 1
        else:
            # Ignorar si tiene otro precio o valor extraño
            ignorados += 1
            continue

        total_identificadas += 1

        # --- ACTUALIZAR ---
        try:
            cur.execute(update_query, (tipo_punto_venta, lecheria_limpia))
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
    print(f"✅ Se actualizaron exitosamente: {len(exitosos)}")
    print(f"👻 Se ignoraron (Subtítulos, celdas vacías o precios distintos): {ignorados}")
    
    if fallidos:
        print(f"\n❌ Fallaron {len(fallidos)} lecherías:")
        for f in fallidos:
            print(f"-> Lechería No. {f['lecheria']} | Motivo: {f['error']}")
    else:
        print("\n🎉 ¡Cero errores! Todas las lecherías procesadas fueron actualizadas.")

if __name__ == '__main__':
    actualizar_tipo_venta()

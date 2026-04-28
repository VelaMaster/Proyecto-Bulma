import pandas as pd
from firebird.driver import connect
from datetime import datetime
import os

# ==========================================
# CONFIGURACIÓN DE BASE DE DATOS (DOCKER)
# ==========================================
# Formato: 'host/port:ruta_interna'
DB_DSN = '127.0.0.1/3050:/firebird/data/DB_SIDISTLOCAL.FDB'
DB_USER = 'SYSDBA'
DB_PASS = 'masterkey'

ARCHIVO_EXCEL = 'OPE012026DIST.xls'

def subir_datos():
    print(f"🚀 Iniciando carga con firebird-driver: {ARCHIVO_EXCEL}")
    
    if not os.path.exists(ARCHIVO_EXCEL):
        print(f"❌ No se encuentra el archivo: {ARCHIVO_EXCEL}")
        return

    try:
        # Fila 9 encabezados (índice 8)
        df = pd.read_excel(ARCHIVO_EXCEL, skiprows=8)
        print(f"📊 Excel leído: {len(df)} filas detectadas.")
    except Exception as e:
        print(f"❌ Error al leer el Excel: {e}")
        return

    print(f"🔗 Conectando a Firebird en {DB_DSN}...")
    try:
        # Pasamos el DSN como primer argumento sin etiqueta 'dsn='
        # y usamos 'user' y 'password' como etiquetas.
        con = connect(DB_DSN, user=DB_USER, password=DB_PASS)
        print("✅ ¡Conectado con éxito!")
    except Exception as e:
        print(f"❌ Error de Conexión: {e}")
        return

    exitosos = []
    fecha_actual = datetime.now()

    insert_query = """
        INSERT INTO INVENTARIO_LEP_SUBSIDIADA (
            LECHER, MES_PERIODO, ANIO_PERIODO, FECHA_TOMA_INVENTARIO,
            INVENTARIO_INICIAL, INVENTARIO_FINAL, VENTA_REAL, SURTIMIENTO,
            VENTA_LIBRO_RETIRO, OBSERVACION, DOTACION_PROGRAMADA, FECHA_HORA_REGISTRO
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    """

    print("📥 Insertando registros...")
    
    with con.cursor() as cur:
        for index, row in df.iterrows():
            def clean(val):
                if pd.isna(val) or str(val).strip() in ['', '-', 'nan']: return 0.0
                try: 
                    return float(str(val).replace(',', '').strip())
                except: 
                    return 0.0

            try:
                # --- MAPEO SEGÚN TU CAPTURA DE PANTALLA ---
                lecheria_raw = row.iloc[0] # Col A
                if pd.isna(lecheria_raw): continue
                
                lecheria_id = int(float(lecheria_raw))
                if lecheria_id <= 0: continue

                mes      = int(clean(row.iloc[2]))  # Col C
                anio     = int(clean(row.iloc[3]))  # Col D
                dot_prog = clean(row.iloc[6])       # Col G (Cajas amarillas)
                vta_real = clean(row.iloc[8])       # Col I
                vta_seg  = clean(row.iloc[11])      # Col L
                surt     = clean(row.iloc[13])      # Col N (Verde)
                inv_ini  = clean(row.iloc[19])      # Col T
                inv_fin  = clean(row.iloc[20])      # Col U

                # EDO. DE OPERACIÓN como observación (Col E)
                obs = str(row.iloc[4])[:100] if pd.notna(row.iloc[4]) else ""

                if mes == 0 or anio == 0: continue

                cur.execute(insert_query, (
                    lecheria_id, mes, anio, fecha_actual.date(), 
                    inv_ini, inv_fin, vta_real, surt, 
                    vta_seg, obs, dot_prog, fecha_actual
                ))
                exitosos.append(lecheria_id)

            except Exception as e:
                # Reportamos errores de datos pero seguimos con la siguiente fila
                if "primary key" in str(e).lower():
                    print(f"⚠️ Lechería {lecheria_id} ya registrada. Saltando...")
                else:
                    print(f"❌ Error en fila {index} (ID: {lecheria_raw}): {e}")

        # Guardar cambios
        if exitosos:
            con.commit()
            print(f"\n✅ PROCESO TERMINADO: {len(exitosos)} lecherías subidas.")
        else:
            print("\n⚠️ No se insertó ningún registro nuevo.")

    con.close()

if __name__ == '__main__':
    subir_datos()
import pandas as pd
import fdb
from datetime import datetime

# ==========================================
# CONFIGURACIÓN DE BASE DE DATOS
# ==========================================
DB_HOST = '172.24.10.251' 
DB_PATH = 'C:/SisDLL20/BD/DB_SIDIST.FDB'
DB_USER = 'SYSDBA'
DB_PASS = '290990'

ARCHIVO_EXCEL = 'OPE032026DIST.xls'

def subir_datos():
    print(f"Leyendo archivo Excel: {ARCHIVO_EXCEL}...")
    try:
        # Saltamos las primeras 4 filas
        df = pd.read_excel(ARCHIVO_EXCEL, skiprows=4)
        df.columns = df.columns.astype(str).str.strip()
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
    fecha_actual = datetime.now().date()

    insert_query = """
        INSERT INTO INVENTARIO_LEP_SUBSIDIADA (
            LECHER, MES_PERIODO, ANIO_PERIODO, FECHA_TOMA_INVENTARIO,
            INVENTARIO_INICIAL, INVENTARIO_FINAL, VENTA_REAL, SURTIMIENTO,
            VENTA_LIBRO_RETIRO, OBSERVACION, DOTACION_PROGRAMADA
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    """

    print("Procesando e insertando lecherías en la base de datos...")
    for index, row in df.iterrows():
        
        # --- 1. FUNCIÓN INTELIGENTE PARA LIMPIAR NÚMEROS (Evita el error del guion '-') ---
        def get_num(posibles_nombres):
            for nombre in posibles_nombres:
                if nombre in df.columns:
                    val = row.get(nombre)
                    if pd.notna(val):
                        val_str = str(val).strip()
                        # Si es un guion o vacío, lo enviamos como NULL a la base de datos
                        if val_str in ['', '-', 'nan', 'NaN']:
                            return None
                        try:
                            return float(val)
                        except ValueError:
                            return None
            return None

        # --- 2. OBTENER Y VALIDAR LA LECHERÍA ---
        lecheria_raw = None
        for col in ['NUMERO LECHERÍA', 'NUMERO LECHERÍA EXT.', 'Lecheria', 'LECHERÍA']:
            if col in df.columns:
                val = row.get(col)
                if pd.notna(val):
                    lecheria_raw = val
                    break
        
        # Si la fila está vacía o es un guion, la ignoramos
        if lecheria_raw is None:
            continue
            
        lecheria_str = str(lecheria_raw).strip()
        if lecheria_str in ['', '-', 'nan', 'NaN']:
            continue
            
        try:
            lecheria_limpia = int(float(lecheria_raw))
        except ValueError:
            fallidos.append({'lecheria': lecheria_str, 'error': 'No es un número válido en el Excel'})
            continue

        # --- 3. EXTRACCIÓN DE LOS DATOS ---
        mes = get_num(['PERIODO MES', 'PERIOD', 'Periodo'])
        anio = get_num(['AÑO', 'ANIO'])
        inv_inicial = get_num(['INV. INICIAL', 'INV.INICIAL'])
        inv_final = get_num(['INVENTARIO FIN', 'INVENTARIO FINAL'])
        venta_real = get_num(['VENTA REAL MES', 'VENTA REAL'])
        surtimiento = get_num(['DOTACIÓN RECIBIDA', 'DOTACIÓN RE'])
        venta_libro = get_num(['VTA.SEG. LIS.RET.', 'VTA.SEG. LIS.'])
        dotacion_prog = get_num(['CAJAS', 'cajas'])

        # Extracción de texto para observaciones
        observacion = ""
        for col in ['OBSERVA', 'OBSERVACIONES']:
            if col in df.columns:
                val = row.get(col)
                if pd.notna(val):
                    val_str = str(val).strip()
                    if val_str not in ['', '-', 'nan', 'NaN']:
                        observacion = val_str
                    break

        # --- 4. INSERTAR EN LA BASE DE DATOS FILA POR FILA ---
        try:
            cur.execute(insert_query, (
                lecheria_limpia, mes, anio, fecha_actual, 
                inv_inicial, inv_final, venta_real, surtimiento, 
                venta_libro, observacion, dotacion_prog
            ))
            # ¡IMPORTANTE! Hacemos commit fila por fila para que las exitosas no se borren si hay error después
            con.commit() 
            exitosos.append(lecheria_limpia)

        except Exception as e:
            # Si hay error (ej. primary key duplicada), solo revertimos esa fila específica
            con.rollback() 
            # Guardamos el error cortado para que no sature la pantalla
            error_msg = str(e).split('\n')[0] 
            fallidos.append({'lecheria': lecheria_limpia, 'error': error_msg})

    cur.close()
    con.close()

    # ==========================================
    # REPORTE FINAL DETALLADO
    # ==========================================
    print("\n==========================================")
    print("        REPORTE DE IMPORTACIÓN            ")
    print("==========================================")
    print(f"✅ Se insertaron {len(exitosos)} lecherías en la base de datos.")
    print(f"❌ Fallaron {len(fallidos)} lecherías.")
    
    if fallidos:
        print("\n--- DETALLE DE LECHERÍAS QUE FALLARON ---")
        for f in fallidos:
            print(f"-> Lechería No. {f['lecheria']} | Motivo: {f['error']}")

if __name__ == '__main__':
    subir_datos()

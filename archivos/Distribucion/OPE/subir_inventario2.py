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
    ignorados = 0 # Nuevo contador para filas de totales/subtítulos
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
        
        def get_num(posibles_nombres):
            for nombre in posibles_nombres:
                if nombre in df.columns:
                    val = row.get(nombre)
                    if pd.notna(val):
                        val_str = str(val).strip()
                        if val_str in ['', '-', 'nan', 'NaN']:
                            return None
                        try:
                            return float(val)
                        except ValueError:
                            return None
            return None

        # --- VALIDACIÓN ESTRICTA ---
        lecheria_raw = None
        for col in ['NUMERO LECHERÍA', 'NUMERO LECHERÍA EXT.', 'Lecheria', 'LECHERÍA']:
            if col in df.columns:
                val = row.get(col)
                if pd.notna(val):
                    lecheria_raw = val
                    break
        
        if lecheria_raw is None:
            continue
            
        lecheria_str = str(lecheria_raw).strip()
        if lecheria_str in ['', '-', 'nan', 'NaN']:
            continue
            
        # EXTRAEMOS EL MES Y EL AÑO PRIMERO PARA VALIDAR
        mes = get_num(['PERIODO MES', 'PERIOD', 'Periodo'])
        anio = get_num(['AÑO', 'ANIO'])

        try:
            lecheria_limpia = int(float(lecheria_raw))
        except ValueError:
            # Si no es un número (ej. "CHALCATONGO"), es un subtítulo. Lo ignoramos.
            ignorados += 1
            continue

        # Si la lechería es un número (ej. "20") PERO no tiene MES o AÑO, es una fila de "Subtotales". La ignoramos.
        if mes is None or anio is None:
            ignorados += 1
            continue

        # Si pasó todas las pruebas, es una lechería real. Extraemos el resto.
        inv_inicial = get_num(['INV. INICIAL', 'INV.INICIAL'])
        inv_final = get_num(['INVENTARIO FIN', 'INVENTARIO FINAL'])
        venta_real = get_num(['VENTA REAL MES', 'VENTA REAL'])
        surtimiento = get_num(['DOTACIÓN RECIBIDA', 'DOTACIÓN RE'])
        venta_libro = get_num(['VTA.SEG. LIS.RET.', 'VTA.SEG. LIS.'])
        dotacion_prog = get_num(['CAJAS', 'cajas'])

        observacion = ""
        for col in ['OBSERVA', 'OBSERVACIONES']:
            if col in df.columns:
                val = row.get(col)
                if pd.notna(val):
                    val_str = str(val).strip()
                    if val_str not in ['', '-', 'nan', 'NaN']:
                        observacion = val_str
                    break

        # --- INSERTAR ---
        try:
            cur.execute(insert_query, (
                lecheria_limpia, mes, anio, fecha_actual, 
                inv_inicial, inv_final, venta_real, surtimiento, 
                venta_libro, observacion, dotacion_prog
            ))
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
    print("        REPORTE DE IMPORTACIÓN            ")
    print("==========================================")
    print(f"✅ Se insertaron {len(exitosos)} lecherías en la base de datos.")
    print(f"👻 Se ignoraron {ignorados} filas (Subtítulos o Totales).")
    
    if fallidos:
        print(f"\n❌ Fallaron {len(fallidos)} lecherías:")
        for f in fallidos:
            print(f"-> Lechería No. {f['lecheria']} | Motivo: {f['error']}")
    else:
        print("\n🎉 ¡Cero errores! Todas las lecherías válidas fueron importadas.")

if __name__ == '__main__':
    subir_datos()

import pandas as pd
from firebird.driver import connect
from datetime import datetime
import os

# ==========================================
# CONFIGURACIÓN DE BASE DE DATOS (DOCKER)
# ==========================================
DB_DSN = '127.0.0.1/3050:/firebird/data/DB_SIDISTLOCAL.FDB'
DB_USER = 'SYSDBA'
DB_PASS = 'masterkey'

ARCHIVO_EXCEL = 'OPE122025DICONSA.xls'

def subir_datos():
    print(f"🚀 Iniciando carga DICONSA: {ARCHIVO_EXCEL}")
    
    if not os.path.exists(ARCHIVO_EXCEL):
        print(f"❌ No se encuentra el archivo: {ARCHIVO_EXCEL}")
        return

    try:
        # En DICONSA pusiste skiprows=4, lo mantenemos igual
        df = pd.read_excel(ARCHIVO_EXCEL, skiprows=4)
        df.columns = df.columns.astype(str).str.strip()
    except Exception as e:
        print(f"❌ Error al leer el Excel: {e}")
        return

    print(f"🔗 Conectando a Firebird...")
    try:
        con = connect(DB_DSN, user=DB_USER, password=DB_PASS)
        print("✅ ¡Conexión establecida!")
    except Exception as e:
        print(f"❌ Error de Conexión: {e}")
        return

    exitosos = []
    fallidos = []
    ignorados = 0
    fecha_actual = datetime.now()

    insert_query = """
        INSERT INTO INVENTARIO_LEP_SUBSIDIADA (
            LECHER, MES_PERIODO, ANIO_PERIODO, FECHA_TOMA_INVENTARIO,
            INVENTARIO_INICIAL, INVENTARIO_FINAL, VENTA_REAL, SURTIMIENTO,
            VENTA_LIBRO_RETIRO, OBSERVACION, DOTACION_PROGRAMADA, FECHA_HORA_REGISTRO
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    """

    print("📥 Procesando e insertando lecherías...")
    
    with con.cursor() as cur:
        for index, row in df.iterrows():
            
            def get_num(posibles_nombres):
                for nombre in posibles_nombres:
                    if nombre in df.columns:
                        val = row.get(nombre)
                        if pd.notna(val):
                            try:
                                return float(str(val).replace(',', '').strip())
                            except: return None
                return None

            # --- VALIDACIÓN DE LECHERÍA ---
            lecheria_raw = None
            for col in ['NUMERO LECHERÍA', 'NUMERO LECHERÍA EXT.', 'Lecheria', 'LECHERÍA']:
                if col in df.columns:
                    val = row.get(col)
                    if pd.notna(val):
                        lecheria_raw = val
                        break
            
            if lecheria_raw is None: continue
            
            try:
                lecheria_limpia = int(float(lecheria_raw))
                if lecheria_limpia <= 0: continue
            except:
                ignorados += 1
                continue

            # --- EXTRACCIÓN DE DATOS (Nombres de DICONSA) ---
            mes = get_num(['PERIODO MES', 'PERIOD'])
            anio = get_num(['AÑO', 'ANIO'])

            if mes is None or anio is None:
                ignorados += 1
                continue

            inv_inicial = get_num(['INV. INICIAL', 'INV.INICIAL'])
            inv_final = get_num(['INVENTARIO FINAL', 'INVENTARIO FIN'])
            venta_real = get_num(['VENTA REAL MES', 'VENTA REAL'])
            surtimiento = get_num(['DOTACIÓN RECIBIDA CAJAS', 'DOTACIÓN RE'])
            venta_libro = get_num(['VTA.SEG. LIS.RET.', 'VTA.SEG. LIS.'])
            dotacion_prog = get_num(['CAJAS', 'cajas'])

            observacion = ""
            for col in ['OBSERVACIONES', 'OBSERVA', 'EDO. DE OPER.']:
                if col in df.columns:
                    val = row.get(col)
                    if pd.notna(val) and str(val).strip() not in ['', '-', 'nan']:
                        observacion = str(val).strip()[:100]
                        break

            # --- EJECUCIÓN ---
            try:
                cur.execute(insert_query, (
                    lecheria_limpia, int(mes), int(anio), fecha_actual.date(), 
                    inv_inicial, inv_final, venta_real, surtimiento, 
                    venta_libro, observacion, dotacion_prog, fecha_actual
                ))
                exitosos.append(lecheria_limpia)
            except Exception as e:
                error_msg = str(e).split('\n')[0]
                if "primary key" in error_msg.lower():
                    print(f"⚠️ ID {lecheria_limpia} ya existe. Saltando...")
                else:
                    fallidos.append({'lecheria': lecheria_limpia, 'error': error_msg})

        # Commit final de todo el archivo
        if exitosos:
            con.commit()
            print(f"\n✅ Transacción completada con éxito.")
        else:
            print("\n⚠️ No se guardó nada nuevo.")

    con.close()

    # ==========================================
    # REPORTE FINAL
    # ==========================================
    print("\n==========================================")
    print("        REPORTE DE IMPORTACIÓN DICONSA    ")
    print("==========================================")
    print(f"✅ Se insertaron {len(exitosos)} lecherías.")
    print(f"👻 Se ignoraron {ignorados} filas (Paja o Totales).")
    
    if fallidos:
        print(f"\n❌ Fallaron {len(fallidos)} registros:")
        for f in fallidos[:5]:
            print(f"-> Lechería {f['lecheria']} | Motivo: {f['error']}")
    else:
        print("\n🎉 ¡Todo listo! Sin errores de base de datos.")

if __name__ == '__main__':
    subir_datos()
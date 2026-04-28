import pandas as pd
from firebird.driver import connect
import os
import re

# ==========================================
# CONFIGURACIÓN DE BASE DE DATOS
# ==========================================
DB_DSN = '127.0.0.1/3050:/firebird/data/DB_SIDISTLOCAL.FDB'
DB_USER = 'SYSDBA'
DB_PASS = 'masterkey'

# Mapeo de nombres a IDs según tu lista
SUPERVISORES_MAP = {
    'FRANCISCO': 1001,
    'MARINO': 1002,
    'MIGUEL ANGEL': 1003,
    'MIGUEL': 1003,
    'OSCAR': 1004,
    'ROQUE': 1005,
    'FIDENCIO': 1006,
    'JOSE DE JESUS': 1007,
    'JOSE': 1007,
    'ERICK': 1008
}

# Configuración de los archivos y sus columnas
ARCHIVOS_A_PROCESAR = [
    {'nombre': 'OPE012026DIST.xls', 'col_sup': 28},   # Col AC
    {'nombre': 'OPE012026DICONSA.xls', 'col_sup': 31} # Col AF
]

def cargar_mapeo_supervisores():
    print("🚀 Iniciando actualización de Mapeo Supervisores-Lecherías")
    
    try:
        con = connect(DB_DSN, user=DB_USER, password=DB_PASS)
        print("✅ Conectado a Firebird.")
    except Exception as e:
        print(f"❌ Error de Conexión: {e}")
        return

    registros_totales = 0

    with con.cursor() as cur:
        # Opcional: Limpiar tabla antes de recargar para evitar duplicados
        # cur.execute("DELETE FROM MAPEO_SUPERVISOR_LECHERIA")
        
        for config in ARCHIVOS_A_PROCESAR:
            archivo = config['nombre']
            col_sup = config['col_sup']

            if not os.path.exists(archivo):
                print(f"⚠️ Saltando {archivo}: No se encuentra el archivo.")
                continue

            print(f"📂 Procesando: {archivo}...")
            try:
                # Leemos el excel saltando los encabezados (fila 9)
                df = pd.read_excel(archivo, skiprows=8, header=None)
            except Exception as e:
                print(f"❌ Error al leer {archivo}: {e}")
                continue

            exitosos_archivo = 0

            for index, row in df.iterrows():
                try:
                    # 1. Obtener y limpiar ID Lechería (Columna A siempre es index 0)
                    lecheria_raw = str(row.iloc[0])
                    match_lech = re.search(r'(\d+)', lecheria_raw)
                    
                    if not match_lech:
                        continue
                    
                    lecheria_id = int(match_lech.group(1))
                    
                    # Filtro básico para IDs reales (ej. 10 dígitos)
                    if lecheria_id < 1000:
                        continue

                    # 2. Obtener y mapear Supervisor
                    sup_nombre_raw = str(row.iloc[col_sup]).upper() if pd.notna(row.iloc[col_sup]) else ""
                    
                    id_supervisor = None
                    for nombre_buscado, id_asociado in SUPERVISORES_MAP.items():
                        if nombre_buscado in sup_nombre_raw:
                            id_supervisor = id_asociado
                            break
                    
                    if id_supervisor:
                        # 3. Insertar en la base de datos
                        # Usamos UPDATE OR INSERT si tu Firebird lo soporta, 
                        # o un try-except para evitar errores de llave primaria.
                        try:
                            query = "INSERT INTO MAPEO_SUPERVISOR_LECHERIA (LECHER, ID_SUPERVISOR) VALUES (?, ?)"
                            cur.execute(query, (lecheria_id, id_supervisor))
                            exitosos_archivo += 1
                        except Exception as e:
                            if "primary key" in str(e).lower():
                                # Si ya existe, actualizamos el supervisor por si cambió en el Excel
                                query_upd = "UPDATE MAPEO_SUPERVISOR_LECHERIA SET ID_SUPERVISOR = ? WHERE LECHER = ?"
                                cur.execute(query_upd, (id_supervisor, lecheria_id))
                                exitosos_archivo += 1
                
                except Exception as e:
                    continue

            print(f"   -> {exitosos_archivo} lecherías vinculadas en este archivo.")
            registros_totales += exitosos_archivo

        if registros_totales > 0:
            con.commit()
            print(f"\n✅ PROCESO TERMINADO: {registros_totales} registros actualizados en MAPEO_SUPERVISOR_LECHERIA.")
        else:
            print("\n⚠️ No se procesaron registros nuevos.")

    con.close()

if __name__ == '__main__':
    cargar_mapeo_supervisores()
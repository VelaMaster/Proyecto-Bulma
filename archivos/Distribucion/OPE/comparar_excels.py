import pandas as pd

# Nombres de tus archivos
FILE_MAESTRO = 'LECHERIAS POR UO Y ALMACEN.xlsx'
FILE_OPE = 'OPE012026DICONSA.xls'

def limpiar_numero(val):
    """Limpia los datos, quita guiones, espacios o vacíos y devuelve un entero."""
    try:
        v_str = str(val).strip()
        if v_str in ['', '-', 'nan', 'NaN', 'None']: 
            return None
        return int(float(v_str))
    except Exception:
        return None

def obtener_lecherias_maestro():
    print(f"Leyendo {FILE_MAESTRO}...")
    # Saltamos la primera fila (índice 0) porque los encabezados están en la segunda
    df = pd.read_excel(FILE_MAESTRO, skiprows=1)
    df.columns = df.columns.astype(str).str.strip().str.upper()
    
    col_name = None
    # Busca la columna tal cual la pusiste en tu ejemplo
    for col in ['NUM DE LECHERIA', 'NUMERO DE LECHERIA', 'LECHERIA']:
        if col in df.columns:
            col_name = col
            break
            
    if not col_name:
        print(f"  ❌ Error: No encontré la columna en {FILE_MAESTRO}.")
        print(f"  Columnas detectadas: {df.columns.tolist()[:10]}") # Imprime para depurar
        return set()
        
    lecherias = set()
    for val in df[col_name]:
        num = limpiar_numero(val)
        # Filtro de seguridad: Una lechería real tiene ~10 dígitos. Ignoramos números pequeños.
        if num and num > 99999: 
            lecherias.add(num)
    return lecherias

def obtener_lecherias_ope():
    print(f"Leyendo {FILE_OPE}...")
    # Este ya sabemos que tiene 4 filas de basura arriba
    df = pd.read_excel(FILE_OPE, skiprows=4)
    df.columns = df.columns.astype(str).str.strip().str.upper()
    
    col_name = None
    for col in ['NUMERO LECHERÍA EXT.', 'NUMERO LECHERÍA', 'LECHERÍA', 'LECHERIA']:
        if col in df.columns:
            col_name = col
            break
            
    if not col_name:
        print(f"  ❌ Error: No encontré la columna en {FILE_OPE}.")
        return set()
        
    lecherias = set()
    for val in df[col_name]:
        num = limpiar_numero(val)
        # Filtramos la "basura" (los subtotales 7, 9, 11, 20... 620)
        if num and num > 99999: 
            lecherias.add(num)
    return lecherias

def comparar():
    print("==========================================")
    print("      AUDITORÍA DE LECHERÍAS (CRUCE)      ")
    print("==========================================\n")
    
    maestro = obtener_lecherias_maestro()
    ope = obtener_lecherias_ope()

    print(f"\n📊 Total lecherías válidas en '{FILE_MAESTRO}': {len(maestro)}")
    print(f"📊 Total lecherías válidas en '{FILE_OPE}': {len(ope)}")

    # MAGIA DE CONJUNTOS DE PYTHON
    coincidencias = maestro & ope  # Las que están en ambos
    faltantes_en_ope = maestro - ope  # Están en el Maestro pero NO en OPE
    extras_en_ope = ope - maestro  # Están en OPE pero NO están en el Maestro

    print("\n==========================================")
    print("                RESULTADOS                ")
    print("==========================================")
    
    print(f"✅ PERFECTAS (Coinciden en ambos): {len(coincidencias)}")
    
    print(f"\n❌ FALTAN EN OPE ({len(faltantes_en_ope)} lecherías):")
    print("   (Están dadas de alta en tu catálogo Maestro, pero no vinieron en el reporte mensual)")
    if faltantes_en_ope:
        print(f"   {sorted(list(faltantes_en_ope))}")
    else:
        print("   ¡Ninguna! OPE tiene todas las lecherías del Maestro.")

    print(f"\n⚠️ SOBRAN EN OPE ({len(extras_en_ope)} lecherías):")
    print("   (Vinieron en el reporte OPE, pero NO existen en tu catálogo Maestro)")
    if extras_en_ope:
        print(f"   {sorted(list(extras_en_ope))}")
    else:
        print("   ¡Ninguna extra! OPE está completamente limpio.")

if __name__ == '__main__':
    comparar()

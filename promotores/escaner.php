<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Scanner Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        :root { --bg-color: #121212; --card-bg: #1e1e1e; --primary: #4285F4; --on-primary: #fff; --border: #444; --text: #e6e1e5; --text-muted: #aaa; }
        body { padding: 16px; background: var(--bg-color); color: var(--text); font-family: 'Roboto', sans-serif; margin: 0; }
        .card { background: var(--card-bg); border-radius: 16px; padding: 16px; margin-bottom: 16px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .section-title { display: flex; align-items: center; gap: 8px; font-size: 1.1rem; color: var(--primary); margin: 0 0 16px 0; border-bottom: 1px solid var(--border); padding-bottom: 8px; }
        .section-badge { background: var(--primary); color: var(--on-primary); width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; }
        .btn-google { background: var(--primary); color: var(--on-primary); width: 100%; padding: 14px; border-radius: 28px; border: none; font-weight: bold; font-size: 16px; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; margin-bottom: 10px; }
        .btn-sync { background: #34A853; color: white; width: 100%; padding: 16px; border-radius: 28px; border: none; font-weight: bold; font-size: 16px; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; margin-top: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.5);}
        #preview { width: 100%; border-radius: 12px; margin-top: 12px; display: none; border: 1px solid var(--border); }
        #status { text-align: center; margin: 12px 0; font-size: 14px; color: var(--primary); font-weight: 500; }

        .input-group { margin-bottom: 12px; }
        .input-group label { display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 4px; }
        .input-group input { width: 100%; background: #2a2a2a; border: 1px solid var(--border); color: #fff; padding: 10px; border-radius: 8px; font-size: 16px; box-sizing: border-box; }
        .input-group input:focus { border-color: var(--primary); outline: none; }

        .table-wrapper { overflow-x: auto; padding-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th { background: #2a2a2a; color: var(--text-muted); font-size: 10px; padding: 8px; text-transform: uppercase; border: 1px solid var(--border); }
        td { padding: 4px; border: 1px solid var(--border); text-align: center; }
        td input { width: 100%; background: transparent; border: none; color: #fff; text-align: center; font-size: 16px; padding: 8px 0; outline: none; }
        td input:focus { background: #333; border-radius: 4px; }
        .row-label { background: #252525; font-weight: 500; font-size: 12px; color: var(--primary); text-align: left; padding-left: 8px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        #log { display: none; background: #000; color: #aaa; font-family: monospace; font-size: 10px; padding: 10px; border-radius: 8px; max-height: 200px; overflow-y: auto; margin-top: 15px; word-wrap: break-word; white-space: pre-wrap;}
    </style>
</head>
<body>

    <div class="card">
        <h2 style="margin:0 0 10px; font-size:1.2rem; text-align:center;">Lector Google Cloud</h2>
        <button class="btn-google" onclick="document.getElementById('inputImagen').click()">
            <span class="material-symbols-outlined">photo_camera</span>
            Escanear Formato
        </button>
        <input type="file" id="inputImagen" accept="image/*" capture="environment" style="display:none;">
        <img id="preview">
        <div id="status">La IA de Google procesará la imagen.</div>

        <button onclick="document.getElementById('log').style.display='block'" style="background:transparent; border:none; color:#555; width:100%; margin-top:10px;">Ver texto detectado por Google</button>
        <div id="log"></div>
    </div>

    <div id="formularioCompleto" style="display: none;">

        <div class="card">
            <h3 class="section-title"><div class="section-badge"><span class="material-symbols-outlined" style="font-size:14px;">description</span></div> Datos Generales</h3>
            <div class="input-group">
                <label>Clave LECHER Detectada</label>
                <input type="text" id="ext_lecher" style="font-weight: bold; color: var(--primary); border-color: var(--primary);">
            </div>
            <div class="input-group">
                <label>Fecha del Formato</label>
                <input type="date" id="ext_fecha">
            </div>
        </div>

        <div class="card">
            <h3 class="section-title"><div class="section-badge">I</div> Existencia de Leche</h3>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th></th>
                            <th>Inv. Inicial</th>
                            <th>Abasto</th>
                            <th>Ventas</th>
                            <th>Litros Reg.</th>
                            <th>Inv. Final</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="row-label">Cajas</td>
                            <td><input type="number" id="ext_ini_c"></td>
                            <td><input type="number" id="ext_aba_c"></td>
                            <td><input type="number" id="ext_ven_c"></td>
                            <td><input type="number" id="ext_reg_c"></td>
                            <td><input type="number" id="ext_fin_c"></td>
                        </tr>
                        <tr>
                            <td class="row-label">Sobres</td>
                            <td><input type="number" id="ext_ini_s"></td>
                            <td><input type="number" id="ext_aba_s"></td>
                            <td><input type="number" id="ext_ven_s"></td>
                            <td><input type="number" id="ext_reg_s"></td>
                            <td><input type="number" id="ext_fin_s"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h3 class="section-title"><div class="section-badge">II</div> Surtimientos</h3>
            <div class="input-group">
                <label>Fecha de Surtimiento</label>
                <input type="date" id="ext_surt_f">
            </div>
            <div class="grid-2">
                <div class="input-group"><label>Cajas</label><input type="number" id="ext_surt_c"></div>
                <div class="input-group"><label>Litros</label><input type="number" id="ext_surt_l"></div>
                <div class="input-group"><label>Facturas</label><input type="text" id="ext_surt_fac"></div>
                <div class="input-group"><label>Caducidad</label><input type="date" id="ext_surt_cad"></div>
            </div>
        </div>

        <div class="card">
            <h3 class="section-title"><div class="section-badge">III</div> Cobertura</h3>
            <div class="grid-2">
                <div class="input-group"><label>Hogares</label><input type="number" id="ext_cob_h"></div>
                <div class="input-group"><label>Menores</label><input type="number" id="ext_cob_m"></div>
                <div class="input-group"><label>Adultas</label><input type="number" id="ext_cob_ma"></div>
                <div class="input-group"><label>Litros al mes</label><input type="number" id="ext_cob_l"></div>
            </div>
        </div>

        <button class="btn-sync" id="btnSincronizar">
            <span class="material-symbols-outlined">send_to_mobile</span>
            Enviar a Laptop
        </button>
        <div id="syncStatus" style="text-align: center; margin-top: 10px; color: #34A853;"></div>
    </div>

    <script>
        const params = new URLSearchParams(window.location.search);
        const token  = params.get('token');

        if (!token) {
            document.getElementById('status').innerHTML = "⚠️ Ábrelo desde el QR de la Laptop para sincronizar.";
            // document.getElementById('formularioCompleto').style.display = 'block'; // Activar para debug visual
        }

        // -----------------------------------------------------------------
        // ÚNICO listener: toma la foto, la manda al OCR y rellena el form.
        // -----------------------------------------------------------------
        document.getElementById('inputImagen').addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const preview = document.getElementById('preview');
            const statusEl = document.getElementById('status');
            const logEl    = document.getElementById('log');

            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
            statusEl.innerHTML = '🚀 Procesando con Google Vision...';
            document.getElementById('formularioCompleto').style.display = 'none';

            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = async () => {
                const base64Image = reader.result.split(',')[1];

                try {
                    const res = await fetch('vision_ocr.php', {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body:    JSON.stringify({ image: base64Image })
                    });
                    const data = await res.json();

                    // Mostrar texto y filas detectadas (debug)
                    let logText = data.texto_completo || 'No se recibió texto del servidor';
                    if (Array.isArray(data.debug_filas)) {
                        logText += '\n\n--- FILAS RECONSTRUIDAS ---\n' + data.debug_filas.join('\n');
                    }
                    logEl.innerText = logText;

                    if (data.status === 'success') {
                        const d = data.datos || {};

                        // Datos Generales
                        setVal('ext_lecher', d.lecher);
                        setVal('ext_fecha',  d.fecha);

                        // Tabla I — Cajas
                        setVal('ext_ini_c', d.ini_c);
                        setVal('ext_aba_c', d.aba_c);
                        setVal('ext_ven_c', d.ven_c);
                        setVal('ext_reg_c', d.reg_c);
                        setVal('ext_fin_c', d.fin_c);

                        // Tabla I — Sobres
                        setVal('ext_ini_s', d.ini_s);
                        setVal('ext_aba_s', d.aba_s);
                        setVal('ext_ven_s', d.ven_s);
                        setVal('ext_reg_s', d.reg_s);
                        setVal('ext_fin_s', d.fin_s);

                        // Tabla II — Surtimientos
                        setVal('ext_surt_f',   d.surt_f);
                        setVal('ext_surt_c',   d.surt_c);
                        setVal('ext_surt_l',   d.surt_l);
                        setVal('ext_surt_fac', d.surt_fac);
                        setVal('ext_surt_cad', d.surt_cad);

                        // Tabla III — Cobertura
                        setVal('ext_cob_h',  d.cob_h);
                        setVal('ext_cob_m',  d.cob_m);
                        setVal('ext_cob_ma', d.cob_ma);
                        setVal('ext_cob_l',  d.cob_l);

                        statusEl.innerHTML = '✅ Formulario autocompletado. Verifica los datos.';
                        document.getElementById('formularioCompleto').style.display = 'block';
                    } else {
                        statusEl.innerHTML = '❌ ' + (data.message || 'Error desconocido en OCR.');
                    }
                } catch (error) {
                    console.error(error);
                    statusEl.innerHTML = '❌ Error de red o servidor: ' + error.message;
                }
            };
        });

        function setVal(id, v) {
            const el = document.getElementById(id);
            if (el) el.value = (v === undefined || v === null) ? '' : v;
        }
    </script>
</body>
</html>

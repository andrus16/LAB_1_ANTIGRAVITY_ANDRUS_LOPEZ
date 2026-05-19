<?php
session_start();
if (!isset($_SESSION["estudiante_logged_in"]) || $_SESSION["estudiante_logged_in"] !== true) {
    header("Location: login_estudiante.php"); exit;
}
$id_estudiante = $_SESSION["estudiante_id"];
$host="localhost"; $dbname="laboratorio_ia"; $db_user="root"; $db_pass="123456789";
$mensaje_examen = ""; $calificacion_obtenida = null;

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $conn->prepare("SELECT nombre, curso, estado, calificacion FROM estudiantes WHERE id=:id");
    $stmt->execute([':id' => $id_estudiante]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$estudiante || $estudiante['curso'] !== 'regresion_lineal' || $estudiante['estado'] !== 'Aprobado') {
        header("Location: perfil.php"); exit;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_examen'])) {
        if ($estudiante['calificacion'] !== null) {
            $mensaje_examen = "Ya has realizado esta evaluación y no puedes enviar respuestas de nuevo.";
            $calificacion_obtenida = $estudiante['calificacion'];
        } else {
            $puntos = 0;
            if (($_POST['q1']??'') === 'predecir') $puntos += 34;
            if (($_POST['q2']??'') === 'm')        $puntos += 33;
            if (($_POST['q3']??'') === 'b')        $puntos += 33;
            $calificacion_obtenida = $puntos;
            $conn->prepare("UPDATE estudiantes SET calificacion=:c WHERE id=:id")
                 ->execute([':c'=>$puntos, ':id'=>$id_estudiante]);
            $estudiante['calificacion'] = $puntos;
            $mensaje_examen = "¡Examen completado! Obtuviste <strong>$puntos/100</strong>. Calificación guardada.";
        }
    }
} catch(PDOException $e) { $mensaje_examen = "Error: ".$e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regresión Lineal - Laboratorio IA</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { overflow: hidden; }
        #particles-js { display: none !important; }
        .dashboard { display:flex; width:100%; height:100vh; position:relative; z-index:2; }

        /* Sidebar */
        .sidebar { width:220px; flex-shrink:0; background:#2b2b2b; border-right:1px solid rgba(0, 212, 255, 0.15); display:flex; flex-direction:column; }
        .sb-header { padding:15px 18px; background-color:#00b4d8; height:50px; display:flex; align-items:center; }
        .sb-logo { font-family:var(--font-display); font-size:1.1rem; font-weight:900; color:#ffffff; letter-spacing:1px; text-transform:uppercase; }
        .sb-logo span { color:#ffffff; }
        .sb-user { padding:16px 18px; border-bottom:1px solid #444; }
        .sb-name { font-size:0.8rem; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:1px; }
        .sb-course { font-size:0.68rem; color:#ccc; margin-top:3px; }
        .sb-score { font-size:0.68rem; color:var(--cyan); margin-top:2px; font-weight:bold; }
        .sb-nav { flex:1; padding:10px 0; }
        .nav-item { display:flex; align-items:center; gap:10px; padding:12px 18px; color:#ccc; font-size:0.85rem; text-decoration:none; cursor:pointer; border-left:4px solid transparent; transition:var(--transition); }
        .nav-item:hover, .nav-item.active { color:#fff; background:#444; border-left-color:var(--cyan); }
        .sb-footer { padding:14px 18px; border-top:1px solid #444; }
        .logout-link { display:flex; align-items:center; gap:8px; color:var(--red); text-decoration:none; font-size:0.82rem; padding:7px 10px; border-radius:6px; transition:var(--transition); }
        .logout-link:hover { background:rgba(255,0,60,0.1); }

        /* Main */
        .main-area { flex:1; overflow-y:auto; display:flex; flex-direction:column; background:#0f1015; color:#e2e8f0; }
        .topbar { padding:14px 28px; border-bottom:1px solid rgba(0, 212, 255, 0.15); background:#15161c; display:flex; align-items:center; justify-content:space-between; height:50px; }
        .topbar-title { font-family:var(--font-display); font-size:0.95rem; color:#ffffff; letter-spacing:1px; text-transform:uppercase; font-weight:700; }
        .content-area { padding:28px; flex:1; z-index:2; }

        /* Tabs */
        .tabs { display:flex; gap:4px; margin-bottom:24px; border-bottom:1px solid rgba(0, 212, 255, 0.15); }
        .tab-btn { padding:9px 18px; background:transparent; border:none; color:#94a3b8; font-family:var(--font-display); font-size:0.75rem; letter-spacing:1px; text-transform:uppercase; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-1px; transition:var(--transition); font-weight:700; }
        .tab-btn:hover { color:var(--cyan); }
        .tab-btn.active { color:var(--cyan); border-bottom-color:var(--cyan); }
        .tab-panel { display:none; }
        .tab-panel.active { display:block; animation:fadeIn 0.3s ease; }
        @keyframes fadeIn { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }

        /* Cards de contenido */
        .content-card { background:rgba(255, 255, 255, 0.03); border:1px solid rgba(0, 212, 255, 0.15); border-radius:var(--radius); padding:24px; margin-bottom:20px; box-shadow:0 4px 20px rgba(0, 212, 255, 0.03); }
        .content-card h3 { font-family:var(--font-display); font-size:0.9rem; color:var(--cyan); letter-spacing:1px; text-transform:uppercase; margin-bottom:14px; font-weight:700; }
        .content-card p, .content-card li { font-size:0.88rem; color:#e2e8f0; line-height:1.7; margin-bottom:8px; }
        .content-card ul { padding-left:20px; }
        .formula-box { text-align:center; background:rgba(0,212,255,0.08); border:1px solid rgba(0,212,255,0.2); border-radius:8px; padding:16px; margin:14px 0; font-family:var(--font-display); font-size:1.3rem; color:#00d4ff; }
        .highlight { color:var(--cyan); font-weight:700; }

        /* Demo */
        .demo-zone { border:1px dashed rgba(0, 212, 255, 0.3); border-radius:var(--radius); padding:20px; background:rgba(0, 0, 0, 0.2); }
        input[type="file"] { width:100%; padding:10px 14px; background:rgba(0,0,0,0.3); border:1px solid rgba(176,38,255,0.3); color:#fff; border-radius:6px; margin-bottom:14px; font-size:0.85rem; cursor:pointer; }
        .result-box { font-family:monospace; font-size:1rem; color:var(--green); background:rgba(0, 255, 136, 0.08); border:1px solid rgba(0, 255, 136, 0.3); border-radius:6px; padding:12px 16px; margin-top:14px; display:none; }
        .chart-wrap { background:#15161c; border-radius:8px; border:1px solid rgba(0, 212, 255, 0.15); padding:12px; margin-top:16px; display:none; }

        /* Examen */
        .question-block { margin-bottom:22px; }
        .question-block label.q-text { font-size:0.88rem; color:#ffffff; font-weight:700; display:block; margin-bottom:10px; }
        .option { display:flex; align-items:center; gap:10px; margin-bottom:7px; }
        .option input[type="radio"] { accent-color:var(--cyan); width:16px; height:16px; cursor:pointer; }
        .option label { font-size:0.85rem; color:#e2e8f0; cursor:pointer; }
        .exam-result { border-radius:8px; padding:14px 18px; margin-bottom:18px; font-size:0.88rem; font-weight:700; }
        .exam-ok  { background:rgba(0, 255, 136, 0.08); color:var(--green); border:1px solid rgba(0, 255, 136, 0.3); }
        .exam-err { background:rgba(255, 0, 60, 0.08);  color:var(--red);   border:1px solid rgba(255, 0, 60, 0.3);  }
    </style>
</head>
<body>
<div id="particles-js"></div>

<div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sb-header">
            <div class="sb-logo">Lab<span>IA</span></div>
        </div>
        <div class="sb-user">
            <div class="sb-name"><?php echo htmlspecialchars($estudiante['nombre']); ?></div>
            <div class="sb-course">Módulo: Regresión Lineal</div>
            <div class="sb-score">Nota: <?php echo $estudiante['calificacion'] ?? 'Sin evaluar'; ?>/100</div>
        </div>
        <nav class="sb-nav">
            <span class="nav-item active">📐 Regresión Lineal</span>
            <a class="nav-item" href="perfil.php">👤 Mi Perfil</a>
        </nav>
        <div class="sb-footer">
            <a href="logout_estudiante.php" class="logout-link">🚪 Cerrar Sesión</a>
        </div>
    </aside>

    <!-- Main -->
    <div class="main-area">
        <div class="topbar">
            <span class="topbar-title">Módulo: Regresión Lineal</span>
            <a href="perfil.php" style="font-size:0.78rem; color:var(--violet); text-decoration:none;">◄ Volver al panel</a>
        </div>

        <div class="content-area">
            <?php if (!empty($mensaje_examen)): ?>
                <div class="exam-result <?php echo $calificacion_obtenida >= 60 ? 'exam-ok':'exam-err'; ?>">
                    <?php echo $mensaje_examen; ?>
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('teoria',this)">📖 Teoría</button>
                <button class="tab-btn" onclick="showTab('ejemplos',this)">💡 Ejemplos</button>
                <button class="tab-btn" onclick="showTab('demo',this)">🧪 Demo CSV</button>
                <button class="tab-btn" onclick="showTab('examen',this)">📝 Examen</button>
            </div>

            <!-- TAB: Teoría -->
            <div id="tab-teoria" class="tab-panel active">
                <div class="content-card">
                    <h3>¿Qué es la Regresión Lineal?</h3>
                    <p>La <span class="highlight">Regresión Lineal Simple</span> es un método estadístico que modela la relación entre dos variables: una <strong>independiente (X)</strong> y una <strong>dependiente (Y)</strong>.</p>
                    <p>El objetivo es encontrar la línea recta que mejor se ajuste a los datos dispersos, minimizando la diferencia entre los valores reales y los predichos (error cuadrático mínimo).</p>
                    <div class="formula-box">Y = mX + b</div>
                    <ul>
                        <li><span class="highlight">Y</span> — Variable a predecir (dependiente).</li>
                        <li><span class="highlight">X</span> — Variable de entrada (independiente).</li>
                        <li><span class="highlight">m</span> — Pendiente: cuánto cambia Y al cambiar X en 1 unidad.</li>
                        <li><span class="highlight">b</span> — Intersección: valor de Y cuando X es 0.</li>
                    </ul>
                </div>
                <div class="content-card">
                    <h3>Fórmulas de Mínimos Cuadrados</h3>
                    <p>Para calcular <strong>m</strong> y <strong>b</strong> matemáticamente a partir de los datos:</p>
                    <div class="formula-box" style="font-size:0.9rem;">m = (n·ΣXY − ΣX·ΣY) / (n·ΣX² − (ΣX)²)<br><br>b = (ΣY − m·ΣX) / n</div>
                    <p>Donde <strong>n</strong> es el número total de puntos de datos.</p>
                </div>
            </div>

            <!-- TAB: Ejemplos -->
            <div id="tab-ejemplos" class="tab-panel">
                <div class="content-card">
                    <h3>Casos de Uso Reales</h3>
                    <ul>
                        <li><span class="highlight">Bienes raíces:</span> Predecir el precio de una vivienda (Y) según sus metros cuadrados (X).</li>
                        <li><span class="highlight">Marketing:</span> Estimar las ventas (Y) en función del presupuesto publicitario (X).</li>
                        <li><span class="highlight">Salud:</span> Relacionar el peso de un paciente (X) con su presión arterial (Y).</li>
                        <li><span class="highlight">Climatología:</span> Predecir temperatura (Y) según altitud (X).</li>
                        <li><span class="highlight">Economía:</span> Proyectar el PIB (Y) en función del consumo interno (X).</li>
                    </ul>
                </div>
                <div class="content-card">
                    <h3>Ejemplo Numérico</h3>
                    <p>Dado el conjunto: (1,2), (2,4), (3,5), (4,4), (5,5)</p>
                    <p>Calculando con mínimos cuadrados obtenemos: <span class="highlight">Y ≈ 0.7X + 1.5</span></p>
                    <p>Esto significa que por cada unidad que aumenta X, Y aumenta en promedio 0.7 unidades.</p>
                </div>
            </div>

            <!-- TAB: Demo -->
            <div id="tab-demo" class="tab-panel">
                <div class="content-card">
                    <h3>Sube tu Dataset CSV</h3>
                    <p style="margin-bottom:16px;">El archivo debe tener <strong>dos columnas numéricas</strong> separadas por coma (X, Y), sin encabezados. Ejemplo:</p>
                    <pre style="background:rgba(0,0,0,0.4);padding:10px;border-radius:6px;color:var(--cyan);font-size:0.82rem;margin-bottom:16px;">1,2.5
2,4.1
3,5.8
4,7.2</pre>
                    <div class="demo-zone">
                        <input type="file" id="csvFile" accept=".csv">
                        <button class="btn-primary" style="width:auto;padding:10px 24px;" onclick="procesarCSV()">⚙ Calcular Regresión</button>
                        <div class="result-box" id="resultBox"></div>
                        <div class="chart-wrap" id="chartWrap">
                            <canvas id="rChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Examen -->
            <div id="tab-examen" class="tab-panel">
                <div class="content-card">
                    <h3>Evaluación Final</h3>
                    <p style="margin-bottom:20px;">Responde correctamente para aprobar el módulo. Tu nota quedará registrada en el sistema.</p>

                    <?php if ($estudiante['calificacion'] !== null): ?>
                        <div class="result-box" style="display:block; margin-bottom: 20px; font-size: 1.1rem;">
                            🎓 Ya has completado esta evaluación final. <br>
                            Tu calificación obtenida es: <strong><?php echo $estudiante['calificacion']; ?>/100</strong>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="curso_regresion_lineal.php">
                            <div class="question-block">
                                <label class="q-text">1. ¿Cuál es el objetivo principal de la Regresión Lineal?</label>
                                <div class="option"><input type="radio" name="q1" value="clasificar" id="q1a"><label for="q1a">Clasificar imágenes</label></div>
                                <div class="option"><input type="radio" name="q1" value="predecir"   id="q1b"><label for="q1b">Predecir una variable a partir de otra</label></div>
                                <div class="option"><input type="radio" name="q1" value="agrupar"    id="q1c"><label for="q1c">Agrupar datos similares</label></div>
                            </div>
                            <div class="question-block">
                                <label class="q-text">2. En Y = mX + b, ¿qué representa "m"?</label>
                                <div class="option"><input type="radio" name="q2" value="m"     id="q2a"><label for="q2a">La pendiente (variación de Y al cambiar X)</label></div>
                                <div class="option"><input type="radio" name="q2" value="error" id="q2b"><label for="q2b">El margen de error</label></div>
                                <div class="option"><input type="radio" name="q2" value="inter" id="q2c"><label for="q2c">La intersección con el eje Y</label></div>
                            </div>
                            <div class="question-block">
                                <label class="q-text">3. En Y = mX + b, ¿qué representa "b"?</label>
                                <div class="option"><input type="radio" name="q3" value="pred"     id="q3a"><label for="q3a">La predicción final</label></div>
                                <div class="option"><input type="radio" name="q3" value="pendiente" id="q3b"><label for="q3b">La pendiente de la línea</label></div>
                                <div class="option"><input type="radio" name="q3" value="b"         id="q3c"><label for="q3c">El valor de Y cuando X es 0 (intersección)</label></div>
                            </div>
                            <button type="submit" name="submit_examen" class="btn-primary" style="width:auto;padding:12px 30px;">📤 Enviar y Guardar Nota</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /content-area -->
    </div><!-- /main-area -->
</div><!-- /dashboard -->

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script src="script.js"></script>
<script>
let myChart = null;

function showTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

function procesarCSV() {
    const file = document.getElementById('csvFile').files[0];
    if (!file) { alert('Selecciona un archivo CSV primero.'); return; }
    const reader = new FileReader();
    reader.onload = e => {
        const lines = e.target.result.trim().split('\n');
        let pts = [], xs = [], ys = [];
        lines.forEach(line => {
            const [x, y] = line.trim().split(',').map(Number);
            if (!isNaN(x) && !isNaN(y)) { xs.push(x); ys.push(y); pts.push({x, y}); }
        });
        if (!xs.length) { alert('No se encontraron datos numéricos válidos.'); return; }

        const n = xs.length;
        const sumX = xs.reduce((a,b)=>a+b,0), sumY = ys.reduce((a,b)=>a+b,0);
        const sumXY = xs.reduce((s,x,i)=>s+x*ys[i],0);
        const sumX2 = xs.reduce((s,x)=>s+x*x,0);
        const m = (n*sumXY - sumX*sumY) / (n*sumX2 - sumX*sumX);
        const b = (sumY - m*sumX) / n;

        const rb = document.getElementById('resultBox');
        rb.style.display = 'block';
        rb.innerHTML = `Ecuación: <strong>Y = ${m.toFixed(4)}X + ${b.toFixed(4)}</strong> &nbsp;|&nbsp; Puntos: ${n}`;

        const minX = Math.min(...xs), maxX = Math.max(...xs);
        const lineData = [{x:minX, y:m*minX+b}, {x:maxX, y:m*maxX+b}];

        document.getElementById('chartWrap').style.display = 'block';
        const ctx = document.getElementById('rChart').getContext('2d');
        if (myChart) myChart.destroy();
        myChart = new Chart(ctx, {
            type: 'scatter',
            data: { datasets: [
                { label: 'Datos CSV', data: pts, backgroundColor: '#b026ff', pointRadius: 5 },
                { label: 'Línea de Regresión', data: lineData, type:'line', borderColor:'#00d4ff', borderWidth:2, fill:false, pointRadius:0 }
            ]},
            options: { responsive:true, scales: {
                x: { title:{display:true, text:'Variable X'} },
                y: { title:{display:true, text:'Variable Y'} }
            }}
        });
    };
    reader.readAsText(file);
}
</script>
</body>
</html>

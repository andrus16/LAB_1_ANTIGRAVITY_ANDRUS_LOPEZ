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
    $stmt = $conn->prepare("SELECT nombre, curso, estado, calificacion_algoritmos_geneticos FROM estudiantes WHERE id=:id");
    $stmt->execute([':id' => $id_estudiante]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$estudiante || $estudiante['curso'] !== 'algoritmos_geneticos' || $estudiante['estado'] !== 'Aprobado') {
        header("Location: perfil.php"); exit;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_examen'])) {
        if ($estudiante['calificacion_algoritmos_geneticos'] !== null) {
            $mensaje_examen = "Ya has realizado esta evaluación y no puedes enviar respuestas de nuevo.";
            $calificacion_obtenida = $estudiante['calificacion_algoritmos_geneticos'];
        } else {
            $puntos = 0;
            if (($_POST['q1']??'') === 'mutacion') $puntos += 34;
            if (($_POST['q2']??'') === 'aptitud')  $puntos += 33;
            if (($_POST['q3']??'') === 'solucion') $puntos += 33;
            $calificacion_obtenida = $puntos;
            $conn->prepare("UPDATE estudiantes SET calificacion_algoritmos_geneticos=:c WHERE id=:id")
                 ->execute([':c'=>$puntos, ':id'=>$id_estudiante]);
            $estudiante['calificacion_algoritmos_geneticos'] = $puntos;
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
    <title>Algoritmos Genéticos - Laboratorio IA</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { overflow: hidden; }
        #particles-js { display: none !important; }
        .dashboard { display:flex; width:100%; height:100vh; position:relative; z-index:2; }

        /* Sidebar */
        .sidebar { width:220px; flex-shrink:0; background:#2b2b2b; border-right:1px solid #e2e8f0; display:flex; flex-direction:column; }
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
        .main-area { flex:1; overflow-y:auto; display:flex; flex-direction:column; background:#fafbfc; }
        .topbar { padding:14px 28px; border-bottom:1px solid #e2e8f0; background:#ffffff; display:flex; align-items:center; justify-content:space-between; height:50px; }
        .topbar-title { font-family:var(--font-display); font-size:0.95rem; color:#0f172a; letter-spacing:1px; text-transform:uppercase; font-weight:700; }
        .content-area { padding:28px; flex:1; z-index:2; }

        /* Tabs */
        .tabs { display:flex; gap:4px; margin-bottom:24px; border-bottom:1px solid #e2e8f0; }
        .tab-btn { padding:9px 18px; background:transparent; border:none; color:#64748b; font-family:var(--font-display); font-size:0.75rem; letter-spacing:1px; text-transform:uppercase; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-1px; transition:var(--transition); font-weight:700; }
        .tab-btn:hover { color:var(--cyan); }
        .tab-btn.active { color:var(--cyan); border-bottom-color:var(--cyan); }
        .tab-panel { display:none; }
        .tab-panel.active { display:block; animation:fadeIn 0.3s ease; }
        @keyframes fadeIn { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }

        /* Cards de contenido */
        .content-card { background:#ffffff; border:1px solid #e2e8f0; border-radius:var(--radius); padding:24px; margin-bottom:20px; box-shadow:0 4px 6px -1px rgba(0, 0, 0, 0.03); }
        .content-card h3 { font-family:var(--font-display); font-size:0.9rem; color:#0077b6; letter-spacing:1px; text-transform:uppercase; margin-bottom:14px; font-weight:700; }
        .content-card p, .content-card li { font-size:0.88rem; color:#1e293b; line-height:1.7; margin-bottom:8px; }
        .content-card ul { padding-left:20px; }
        .formula-box { text-align:center; background:rgba(0,180,216,0.05); border:1px solid rgba(0,180,216,0.2); border-radius:8px; padding:16px; margin:14px 0; font-family:var(--font-display); font-size:1.3rem; color:#0ea5e9; }
        .highlight { color:#0077b6; font-weight:700; }

        /* Demo */
        .demo-zone { border:1px dashed rgba(0,180,216,0.3); border-radius:var(--radius); padding:20px; background:#f8fafc; }
        .result-box { font-family:monospace; font-size:1rem; color:var(--green); background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px; padding:12px 16px; margin-top:14px; display:none; }
        .chart-wrap { background:#fff; border-radius:8px; border:1px solid #e2e8f0; padding:12px; margin-top:16px; display:none; }

        /* Examen */
        .question-block { margin-bottom:22px; }
        .question-block label.q-text { font-size:0.88rem; color:#1e293b; font-weight:700; display:block; margin-bottom:10px; }
        .option { display:flex; align-items:center; gap:10px; margin-bottom:7px; }
        .option input[type="radio"] { accent-color:var(--cyan); width:16px; height:16px; cursor:pointer; }
        .option label { font-size:0.85rem; color:#475569; cursor:pointer; }
        .exam-result { border-radius:8px; padding:14px 18px; margin-bottom:18px; font-size:0.88rem; font-weight:700; }
        .exam-ok  { background:#f0fdf4; color:var(--green); border:1px solid #bbf7d0; }
        .exam-err { background:#fef2f2;  color:var(--red);   border:1px solid #fecaca;  }
        
        .sim-input {
            width: 100%; max-width: 200px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 12px; display: block; background: #fff; color: #000;
        }
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
            <div class="sb-course">Módulo: Algoritmos Genéticos</div>
            <div class="sb-score">Nota: <?php echo $estudiante['calificacion_algoritmos_geneticos'] ?? 'Sin evaluar'; ?>/100</div>
        </div>
        <nav class="sb-nav">
            <span class="nav-item active">🧬 Algoritmos Genéticos</span>
            <a class="nav-item" href="perfil.php">👤 Mi Perfil</a>
        </nav>
        <div class="sb-footer">
            <a href="logout_estudiante.php" class="logout-link">🚪 Cerrar Sesión</a>
        </div>
    </aside>

    <!-- Main -->
    <div class="main-area">
        <div class="topbar">
            <span class="topbar-title">Módulo: Algoritmos Genéticos</span>
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
                <button class="tab-btn" onclick="showTab('demo',this)">🧪 Demo Evolutiva</button>
                <button class="tab-btn" onclick="showTab('examen',this)">📝 Examen</button>
            </div>

            <!-- TAB: Teoría -->
            <div id="tab-teoria" class="tab-panel active">
                <div class="content-card">
                    <h3>¿Qué son los Algoritmos Genéticos?</h3>
                    <p>Un <span class="highlight">Algoritmo Genético (AG)</span> es un método de optimización y búsqueda inspirado en el proceso de selección natural y la genética biológica.</p>
                    <p>Partiendo de una población aleatoria de soluciones (individuos), el algoritmo las evalúa y selecciona las mejores para cruzarlas y mutarlas, generando nuevas generaciones superiores.</p>
                    <div class="formula-box">Aptitud(x) = f(cromosoma)</div>
                    <ul>
                        <li><span class="highlight">Cromosoma:</span> Codificación digital de una solución candidata.</li>
                        <li><span class="highlight">Población:</span> Conjunto de soluciones activas en una generación.</li>
                        <li><span class="highlight">Selección:</span> Priorizar la reproducción de los individuos con mayor aptitud.</li>
                        <li><span class="highlight">Cruce (Crossover):</span> Combinar genes de dos padres para crear descendientes.</li>
                        <li><span class="highlight">Mutación:</span> Alterar aleatoriamente algunos genes para mantener diversidad.</li>
                    </ul>
                </div>
            </div>

            <!-- TAB: Ejemplos -->
            <div id="tab-ejemplos" class="tab-panel">
                <div class="content-card">
                    <h3>Aplicaciones de los AG</h3>
                    <ul>
                        <li><span class="highlight">Problema del Viajante (TSP):</span> Encontrar la ruta más corta para visitar múltiples ciudades sin repetir.</li>
                        <li><span class="highlight">Optimización de Redes:</span> Diseñar la topología de fibra óptica óptima con costes mínimos.</li>
                        <li><span class="highlight">Robótica y Control:</span> Evolucionar el equilibrio y movimiento físico de robots bípedos.</li>
                        <li><span class="highlight">Finanzas:</span> Optimizar carteras de inversión seleccionando activos que maximicen retorno y minimicen riesgo.</li>
                    </ul>
                </div>
            </div>

            <!-- TAB: Demo -->
            <div id="tab-demo" class="tab-panel">
                <div class="content-card">
                    <h3>Simulador Evolutivo de Texto</h3>
                    <p style="margin-bottom:16px;">Observa cómo la selección natural y la mutación evolucionan una población aleatoria para formar la palabra objetivo <strong>"IA"</strong>:</p>
                    
                    <div class="demo-zone">
                        <label style="color:#000; font-size:0.8rem; font-weight:bold;">Tamaño de Población:</label>
                        <input type="number" id="popSize" class="sim-input" value="100" min="10" max="500">
                        
                        <label style="color:#000; font-size:0.8rem; font-weight:bold;">Tasa de Mutación (0 a 1):</label>
                        <input type="number" id="mutRate" class="sim-input" value="0.05" step="0.01" min="0" max="1">

                        <button class="btn-primary" style="width:auto;padding:10px 24px;" onclick="iniciarSimulacion()">⚙ Iniciar Evolución</button>
                        
                        <div class="result-box" id="resultBox"></div>
                        <div class="chart-wrap" id="chartWrap">
                            <canvas id="evolChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Examen -->
            <div id="tab-examen" class="tab-panel">
                <div class="content-card">
                    <h3>Evaluación Final</h3>
                    <p style="margin-bottom:20px;">Responde correctamente para aprobar el módulo. Tu nota quedará registrada en el sistema.</p>

                    <?php if ($estudiante['calificacion_algoritmos_geneticos'] !== null): ?>
                        <div class="result-box" style="display:block; margin-bottom: 20px; font-size: 1.1rem;">
                            🎓 Ya has completado esta evaluación final. <br>
                            Tu calificación obtenida es: <strong><?php echo $estudiante['calificacion_algoritmos_geneticos']; ?>/100</strong>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="curso_algoritmos_geneticos.php">
                            <div class="question-block">
                                <label class="q-text">1. ¿Qué proceso introduce variabilidad aleatoria en la descendencia?</label>
                                <div class="option"><input type="radio" name="q1" value="seleccion" id="q1a"><label for="q1a">Selección natural</label></div>
                                <div class="option"><input type="radio" name="q1" value="mutacion"   id="q1b"><label for="q1b">Mutación aleatoria</label></div>
                                <div class="option"><input type="radio" name="q1" value="cruce"      id="q1c"><label for="q1c">Cruce simple</label></div>
                            </div>
                            <div class="question-block">
                                <label class="q-text">2. ¿Qué evalúa la "función de aptitud"?</label>
                                <div class="option"><input type="radio" name="q2" value="tasa"    id="q2a"><label for="q2a">La velocidad de ejecución</label></div>
                                <div class="option"><input type="radio" name="q2" value="aptitud" id="q2b"><label for="q2b">Qué tan buena es una solución candidata</label></div>
                                <div class="option"><input type="radio" name="q2" value="genes"   id="q2c"><label for="q2c">El número de genes por cromosoma</label></div>
                            </div>
                            <div class="question-block">
                                <label class="q-text">3. En algoritmos genéticos, ¿qué representa un individuo?</label>
                                <div class="option"><input type="radio" name="q3" value="solucion" id="q3a"><label for="q3a">Una solución candidata al problema</label></div>
                                <div class="option"><input type="radio" name="q3" value="hardware" id="q3b"><label for="q3b">Un chip físico de procesamiento</label></div>
                                <div class="option"><input type="radio" name="q3" value="usuario"  id="q3c"><label for="q3c">El usuario que ejecuta el sistema</label></div>
                            </div>
                            <button type="submit" name="submit_examen" class="btn-primary" style="width:auto;padding:12px 30px;">📤 Enviar y Guardar Nota</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /content-area -->
    </div><!-- /main-area -->
</div><!-- /dashboard -->

<script>
let myChart = null;

function showTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

function iniciarSimulacion() {
    const popSize = parseInt(document.getElementById('popSize').value) || 100;
    const mutRate = parseFloat(document.getElementById('mutRate').value) || 0.05;
    const target = "IA";
    const chars = "ABCDEFGHIJKLMNÑOPQRSTUVWXYZ ";
    
    let population = [];
    for(let i=0; i<popSize; i++) {
        let gene = "";
        for(let j=0; j<target.length; j++) gene += chars[Math.floor(Math.random()*chars.length)];
        population.push(gene);
    }

    let gen = 0;
    let fitnessScores = [];
    let bestString = "";
    let historyGenerations = [];
    let historyBestFitness = [];

    while (bestString !== target && gen < 100) {
        gen++;
        let fitness = population.map(ind => {
            let score = 0;
            for(let j=0; j<target.length; j++) if(ind[j] === target[j]) score++;
            return score;
        });

        let maxFit = Math.max(...fitness);
        let bestIdx = fitness.indexOf(maxFit);
        bestString = population[bestIdx];

        historyGenerations.push(gen);
        historyBestFitness.push((maxFit / target.length) * 100);

        let pool = [];
        for(let i=0; i<popSize; i++) {
            let n = fitness[i] * 5 + 1;
            for(let k=0; k<n; k++) pool.push(population[i]);
        }

        let newPop = [];
        for(let i=0; i<popSize; i++) {
            let parentA = pool[Math.floor(Math.random()*pool.length)];
            let parentB = pool[Math.floor(Math.random()*pool.length)];
            
            let child = "";
            let mid = Math.floor(Math.random()*target.length);
            for(let j=0; j<target.length; j++) {
                if(Math.random() < mutRate) {
                    child += chars[Math.floor(Math.random()*chars.length)];
                } else {
                    child += (j < mid) ? parentA[j] : parentB[j];
                }
            }
            newPop.push(child);
        }
        population = newPop;
    }

    const rb = document.getElementById('resultBox');
    rb.style.display = 'block';
    rb.innerHTML = `Evolución finalizada en <strong>Generación ${gen}</strong> &nbsp;|&nbsp; Mejor palabra obtenida: <strong>"${bestString}"</strong>`;

    document.getElementById('chartWrap').style.display = 'block';
    const ctx = document.getElementById('evolChart').getContext('2d');
    if (myChart) myChart.destroy();
    myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: historyGenerations,
            datasets: [{
                label: 'Aptitud del Mejor (%)',
                data: historyBestFitness,
                borderColor: '#00d4ff',
                backgroundColor: 'rgba(0,212,255,0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: { title: { display: true, text: 'Generación' } },
                y: { min: 0, max: 100, title: { display: true, text: 'Aptitud (%)' } }
            }
        }
    });
}
</script>
</body>
</html>

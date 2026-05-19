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
    $stmt = $conn->prepare("SELECT nombre, curso, estado, calificacion_machine_learning FROM estudiantes WHERE id=:id");
    $stmt->execute([':id' => $id_estudiante]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$estudiante || $estudiante['curso'] !== 'machine_learning' || $estudiante['estado'] !== 'Aprobado') {
        header("Location: perfil.php"); exit;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_examen'])) {
        if ($estudiante['calificacion_machine_learning'] !== null) {
            $mensaje_examen = "Ya has realizado esta evaluación y no puedes enviar respuestas de nuevo.";
            $calificacion_obtenida = $estudiante['calificacion_machine_learning'];
        } else {
            $puntos = 0;
            if (($_POST['q1']??'') === 'supervisado') $puntos += 34;
            if (($_POST['q2']??'') === 'agrupar')     $puntos += 33;
            if (($_POST['q3']??'') === 'overfitting')  $puntos += 33;
            $calificacion_obtenida = $puntos;
            $conn->prepare("UPDATE estudiantes SET calificacion_machine_learning=:c WHERE id=:id")
                 ->execute([':c'=>$puntos, ':id'=>$id_estudiante]);
            $estudiante['calificacion_machine_learning'] = $puntos;
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
    <title>Machine Learning - Laboratorio IA</title>
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
            <div class="sb-course">Módulo: Machine Learning</div>
            <div class="sb-score">Nota: <?php echo $estudiante['calificacion_machine_learning'] ?? 'Sin evaluar'; ?>/100</div>
        </div>
        <nav class="sb-nav">
            <span class="nav-item active">🤖 Machine Learning</span>
            <a class="nav-item" href="perfil.php">👤 Mi Perfil</a>
        </nav>
        <div class="sb-footer">
            <a href="logout_estudiante.php" class="logout-link">🚪 Cerrar Sesión</a>
        </div>
    </aside>

    <!-- Main -->
    <div class="main-area">
        <div class="topbar">
            <span class="topbar-title">Módulo: Machine Learning</span>
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
                <button class="tab-btn" onclick="showTab('demo',this)">🧪 Demo K-Means</button>
                <button class="tab-btn" onclick="showTab('examen',this)">📝 Examen</button>
            </div>

            <!-- TAB: Teoría -->
            <div id="tab-teoria" class="tab-panel active">
                <div class="content-card">
                    <h3>¿Qué es el Aprendizaje Automático (Machine Learning)?</h3>
                    <p>El <span class="highlight">Machine Learning (ML)</span> es una rama de la Inteligencia Artificial que permite a los sistemas informáticos aprender de forma autónoma a partir de datos sin ser programados explícitamente.</p>
                    <p>Se divide en tres metodologías principales:</p>
                    <ul>
                        <li><span class="highlight">Aprendizaje Supervisado:</span> El algoritmo se entrena con datos previamente etiquetados (ej. predecir precios).</li>
                        <li><span class="highlight">Aprendizaje No Supervisado:</span> El sistema descubre patrones ocultos y agrupaciones naturales en datos no etiquetados (ej. clustering K-Means).</li>
                        <li><span class="highlight">Aprendizaje por Refuerzo:</span> Un agente aprende mediante recompensas y castigos interactuando con su entorno.</li>
                    </ul>
                    <div class="formula-box">Loss = Σ (y_real - y_pred)²</div>
                </div>
            </div>

            <!-- TAB: Ejemplos -->
            <div id="tab-ejemplos" class="tab-panel">
                <div class="content-card">
                    <h3>Casos Prácticos de ML</h3>
                    <ul>
                        <li><span class="highlight">Filtros de Spam:</span> Clasificar correos electrónicos en "deseados" y "no deseados".</li>
                        <li><span class="highlight">Reconocimiento Facial:</span> Detección y clasificación de rostros humanos en imágenes.</li>
                        <li><span class="highlight">Sistemas de Recomendación:</span> Proyección de películas o música de interés para el usuario.</li>
                        <li><span class="highlight">Vehículos Autónomos:</span> Toma de decisiones de navegación en tiempo real basada en sensores.</li>
                    </ul>
                </div>
            </div>

            <!-- TAB: Demo -->
            <div id="tab-demo" class="tab-panel">
                <div class="content-card">
                    <h3>Simulador de Agrupamiento K-Means</h3>
                    <p style="margin-bottom:16px;">Configura el número de grupos (K) y observa cómo los datos se agrupan de forma autónoma según su cercanía matemática:</p>
                    
                    <div class="demo-zone">
                        <label style="color:#000; font-size:0.8rem; font-weight:bold;">Número de Clusters (K):</label>
                        <input type="number" id="numClusters" class="sim-input" value="3" min="2" max="5">
                        
                        <button class="btn-primary" style="width:auto;padding:10px 24px;" onclick="ejecutarKMeans()">⚙ Generar y Agrupar</button>
                        
                        <div class="result-box" id="resultBox"></div>
                        <div class="chart-wrap" id="chartWrap">
                            <canvas id="kmeansChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Examen -->
            <div id="tab-examen" class="tab-panel">
                <div class="content-card">
                    <h3>Evaluación Final</h3>
                    <p style="margin-bottom:20px;">Responde correctamente para aprobar el módulo. Tu nota quedará registrada en el sistema.</p>

                    <?php if ($estudiante['calificacion_machine_learning'] !== null): ?>
                        <div class="result-box" style="display:block; margin-bottom: 20px; font-size: 1.1rem;">
                            🎓 Ya has completado esta evaluación final. <br>
                            Tu calificación obtenida es: <strong><?php echo $estudiante['calificacion_machine_learning']; ?>/100</strong>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="curso_machine_learning.php">
                            <div class="question-block">
                                <label class="q-text">1. ¿Qué tipo de aprendizaje utiliza datos etiquetados para entrenar modelos?</label>
                                <div class="option"><input type="radio" name="q1" value="no_supervisado" id="q1a"><label for="q1a">No Supervisado</label></div>
                                <div class="option"><input type="radio" name="q1" value="supervisado"    id="q1b"><label for="q1b">Supervisado</label></div>
                                <div class="option"><input type="radio" name="q1" value="refuerzo"       id="q1c"><label for="q1c">Por Refuerzo</label></div>
                            </div>
                            <div class="question-block">
                                <label class="q-text">2. ¿Cuál es la finalidad principal del algoritmo K-Means?</label>
                                <div class="option"><input type="radio" name="q2" value="predecir" id="q2a"><label for="q2a">Predecir un valor de precio exacto</label></div>
                                <div class="option"><input type="radio" name="q2" value="agrupar"  id="q2b"><label for="q2b">Agrupar datos en K grupos basados en distancia</label></div>
                                <div class="option"><input type="radio" name="q2" value="eliminar" id="q2c"><label for="q2c">Eliminar datos duplicados</label></div>
                            </div>
                            <div class="question-block">
                                <label class="q-text">3. ¿Qué ocurre cuando un modelo memoriza los datos de entrenamiento y falla con nuevos datos?</label>
                                <div class="option"><input type="radio" name="q3" value="overfitting" id="q3a"><label for="q3a">Overfitting / Sobreajuste</label></div>
                                <div class="option"><input type="radio" name="q3" value="underfitting" id="q3b"><label for="q3b">Underfitting / Subajuste</label></div>
                                <div class="option"><input type="radio" name="q3" value="optimizacion" id="q3c"><label for="q3c">Optimización Genética</label></div>
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

function ejecutarKMeans() {
    const K = parseInt(document.getElementById('numClusters').value) || 3;
    const numPoints = 80;
    
    // Generar puntos aleatorios en el plano 2D
    let points = [];
    for(let i=0; i<numPoints; i++) {
        points.push({ x: Math.random()*100, y: Math.random()*100 });
    }

    // Inicializar centroides de manera aleatoria
    let centroids = [];
    for(let i=0; i<K; i++) {
        centroids.push({ x: Math.random()*100, y: Math.random()*100 });
    }

    let clusters = Array.from({length: K}, () => []);

    // Ejecutar 5 iteraciones simples de K-Means
    for(let iter=0; iter<5; iter++) {
        clusters = Array.from({length: K}, () => []);
        
        // Asignar puntos al centroide más cercano
        points.forEach(p => {
            let minDist = Infinity;
            let bestCluster = 0;
            centroids.forEach((c, idx) => {
                let dist = Math.hypot(p.x - c.x, p.y - c.y);
                if (dist < minDist) {
                    minDist = dist;
                    bestCluster = idx;
                }
            });
            clusters[bestCluster].push(p);
        });

        // Recalcular centroides
        centroids = centroids.map((c, idx) => {
            const list = clusters[idx];
            if (list.length === 0) return c;
            let sumX = list.reduce((s, p) => s + p.x, 0);
            let sumY = list.reduce((s, p) => s + p.y, 0);
            return { x: sumX / list.length, y: sumY / list.length };
        });
    }

    // Mostrar resultados
    const rb = document.getElementById('resultBox');
    rb.style.display = 'block';
    rb.innerHTML = `K-Means finalizado con <strong>K = ${K}</strong> clusters recalculados exitosamente.`;

    document.getElementById('chartWrap').style.display = 'block';
    const ctx = document.getElementById('kmeansChart').getContext('2d');
    if (myChart) myChart.destroy();

    const colors = ['#00d4ff', '#b026ff', '#00ff88', '#f59e0b', '#ff00c1'];
    let datasets = [];

    // Agregar clusters a los datasets
    for(let i=0; i<K; i++) {
        datasets.push({
            label: `Cluster ${i+1}`,
            data: clusters[i],
            backgroundColor: colors[i % colors.length],
            pointRadius: 6
        });
    }

    // Agregar centroides como puntos grandes
    datasets.push({
        label: 'Centroides',
        data: centroids,
        backgroundColor: '#000',
        borderColor: '#fff',
        borderWidth: 2,
        pointRadius: 10,
        pointStyle: 'rectRot'
    });

    myChart = new Chart(ctx, {
        type: 'scatter',
        data: { datasets: datasets },
        options: {
            responsive: true,
            scales: {
                x: { min: 0, max: 100, title: { display: true, text: 'Variable X' } },
                y: { min: 0, max: 100, title: { display: true, text: 'Variable Y' } }
            }
        }
    });
}
</script>
</body>
</html>

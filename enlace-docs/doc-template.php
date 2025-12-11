<?php
// Plantilla genérica para mostrar un documento en página dedicada.
// Requiere que $docData esté definido antes de incluir este archivo.
session_start();
require_once '../includes/functions.php';
$asset_version = '2024-12-11-2';

if (!isset($docData) || empty($docData['file'])) {
    http_response_code(404);
    die('Documento no disponible: datos no definidos.');
}

// Normalizar la ruta del archivo usando realpath para resolver ../ y ./
$filePath = $docData['file'];

// Intentar resolver la ruta con realpath (resuelve ../ y ./)
$resolvedPath = realpath($filePath);
if ($resolvedPath !== false && file_exists($resolvedPath)) {
    $filePath = $resolvedPath;
} else {
    // Si realpath falla, construir la ruta desde el directorio base
    $baseDir = dirname(__DIR__);
    $fileName = basename($filePath);
    $expectedPath = $baseDir . DIRECTORY_SEPARATOR . 'Docs' . DIRECTORY_SEPARATOR . 'Documentacion-Requerida' . DIRECTORY_SEPARATOR . $fileName;
    
    $resolvedExpected = realpath($expectedPath);
    if ($resolvedExpected !== false && file_exists($resolvedExpected)) {
        $filePath = $resolvedExpected;
    } else if (file_exists($expectedPath)) {
        $filePath = $expectedPath;
    }
}

if (!file_exists($filePath)) {
    http_response_code(404);
    die('Documento no disponible.');
}

$docContent = file_get_contents($filePath);
if ($docContent === false) {
    http_response_code(500);
    die('Error al leer el documento.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($docData['title']); ?> - ActividadesConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo $asset_version; ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="docs-main">
        <div class="container">
            <div class="doc-page-hero">
                <div class="doc-hero-left">
                    <div class="doc-hero-icon">
                        <i class="fas <?php echo $docData['icon']; ?>"></i>
                    </div>
                    <div>
                        <p class="eyebrow">Documento</p>
                        <h1><?php echo htmlspecialchars($docData['title']); ?></h1>
                        <div class="doc-meta hero-meta">
                            <span><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars(basename($filePath)); ?></span>
                            <span><i class="fas fa-clock"></i> Actualizado: <?php echo date('d/m/Y', filemtime($filePath)); ?></span>
                            <span><i class="fas fa-user"></i> Propietario: Adán Moreno Carrera</span>
                        </div>
                    </div>
                </div>
                <div class="doc-hero-actions">
                    <a class="btn-ghost" href="../documentacion-requerida.php"><i class="fas fa-arrow-left"></i> Volver al índice</a>
                    <button class="btn-ghost" id="download-pdf" type="button"><i class="fas fa-file-pdf"></i> Descargar PDF</button>
                </div>
            </div>

            <div class="doc-card">
                <div class="doc-card-body doc-layout">
                    <div class="doc-main" id="doc-export">
                        <div class="doc-export-header">
                            <p class="eyebrow">Documento</p>
                            <h2><?php echo htmlspecialchars($docData['title']); ?></h2>
                            <div class="doc-meta">
                                <span><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars(basename($filePath)); ?></span>
                                <span><i class="fas fa-clock"></i> Actualizado: <?php echo date('d/m/Y', filemtime($filePath)); ?></span>
                                <span><i class="fas fa-user"></i> Propietario: Adán Moreno Carrera</span>
                            </div>
                        </div>
                        <div class="doc-render" id="doc-render"></div>
                        <textarea id="doc-source" hidden><?php echo htmlspecialchars($docContent, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <aside class="doc-toc" id="doc-toc">
                        <p class="doc-toc-title"><i class="fas fa-list-ul"></i> En esta página</p>
                        <ul id="doc-toc-list"></ul>
                    </aside>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js"></script>
    <script>
        (function() {
            const source = document.getElementById('doc-source');
            const target = document.getElementById('doc-render');
            const tocList = document.getElementById('doc-toc-list');
            const tocBox = document.getElementById('doc-toc');
            const downloadBtn = document.getElementById('download-pdf');
            const exportEl = document.getElementById('doc-export');
            if (!source || !target) return;

            target.innerHTML = marked.parse(source.value, { breaks: true });

            // Asegurar IDs en los títulos para anclajes
            const headings = target.querySelectorAll('h2, h3');
            const slugify = (text) => text.toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/\s+/g, '-');

            headings.forEach((h, idx) => {
                if (!h.id) {
                    h.id = slugify(`${h.textContent}-${idx}`);
                }
            });

            // Construir TOC solo con los puntos del índice inicial y ocultar ese índice en el contenido
            const buildTocFromIndex = () => {
                if (!tocList || !tocBox) return false;
                tocList.innerHTML = '';
                let indexHeading = null;
                headings.forEach(h => {
                    if (!indexHeading && h.textContent.toLowerCase().includes('índice')) {
                        indexHeading = h;
                    }
                });
                if (!indexHeading) return false;
                let list = indexHeading.nextElementSibling;
                if (!list || !['UL','OL'].includes(list.tagName)) return false;

                const items = list.querySelectorAll('li');
                if (!items.length) return false;

                const ensureAnchor = (text, hrefCandidate) => {
                    const anchor = (hrefCandidate && hrefCandidate.startsWith('#'))
                        ? hrefCandidate.slice(1)
                        : slugify(text);
                    // Buscar un heading que ya tenga ese id o coincida por texto
                    let targetHeading = Array.from(headings).find(h => h.id === anchor);
                    if (!targetHeading) {
                        targetHeading = Array.from(headings).find(h => slugify(h.textContent) === anchor);
                    }
                    if (targetHeading) {
                        targetHeading.id = anchor;
                        return `#${anchor}`;
                    }
                    return `#${anchor}`;
                };

                items.forEach((item) => {
                    const link = item.querySelector('a');
                    const text = link ? link.textContent : item.textContent;
                    const href = ensureAnchor(text, link ? link.getAttribute('href') : null);
                    const li = document.createElement('li');
                    li.className = 'toc-level-2';
                    const a = document.createElement('a');
                    a.href = href;
                    a.textContent = text;
                    li.appendChild(a);
                    tocList.appendChild(li);
                });

                // Ocultar el índice original en el contenido
                indexHeading.remove();
                list.remove();
                return true;
            };

            const built = buildTocFromIndex();
            if (!built && tocBox) tocBox.style.display = 'none';

            if (downloadBtn && exportEl) {
                downloadBtn.addEventListener('click', () => {
                    const opt = {
                        margin:       0.5,
                        filename:     (document.title || 'documentacion') + '.pdf',
                        image:        { type: 'jpeg', quality: 0.98 },
                        html2canvas:  { scale: 2 },
                        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
                    };
                    html2pdf().set(opt).from(exportEl).save();
                });
            }
        })();
    </script>
</body>
</html>


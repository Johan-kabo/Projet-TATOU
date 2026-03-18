<?php
$activePage = 'stats';
$pageTitle = 'Statistiques Avancées';
require_once __DIR__ . '/includes/header.php';

$db = getDb();

// Example data for charts (placeholder). You can replace with computed values.
$studentsByProgram = $db->query('SELECT program, COUNT(*) AS count FROM students GROUP BY program')->fetchAll();
$successByLevel = [
    'L1' => 75,
    'L2' => 82,
    'L3' => 88,
    'L4' => 91,
];
?>

<h1 class="page__title">Statistiques Avancées</h1>
<p class="page__subtitle">Analyse détaillée des performances de TAAJ Corp.</p>

<div class="grid">
    <div class="panel">
        <div class="panel__header">
            <h2>Répartition par Filière</h2>
        </div>
        <div class="panel__content">
            <canvas id="programChart" width="400" height="300"></canvas>
            <div id="programLegend" style="display: flex; justify-content: center; gap: 20px; margin-top: 20px; flex-wrap: wrap;"></div>
        </div>
    </div>
    <div class="panel">
        <div class="panel__header">
            <h2>Taux de Réussite par Niveau</h2>
        </div>
        <div class="panel__content">
            <canvas id="successChart" width="400" height="300"></canvas>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Program distribution donut chart
    const programCtx = document.getElementById('programChart').getContext('2d');
    const programData = <?php 
        $data = [];
        foreach ($studentsByProgram as $row) {
            $data[] = [
                'label' => $row['program'],
                'count' => (int) $row['count']
            ];
        }
        echo json_encode($data);
    ?>;
    
    const colors = ['#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#8b5cf6'];
    
    const programChart = new Chart(programCtx, {
        type: 'doughnut',
        data: {
            labels: programData.map(d => d.label),
            datasets: [{
                data: programData.map(d => d.count),
                backgroundColor: colors.slice(0, programData.length),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    }
                }
            },
            cutout: '60%'
        }
    });
    
    // Create custom legend
    const legendContainer = document.getElementById('programLegend');
    programData.forEach((item, index) => {
        const legendItem = document.createElement('div');
        legendItem.style.cssText = 'display: flex; align-items: center; gap: 8px;';
        legendItem.innerHTML = `
            <span style="width: 12px; height: 12px; background: ${colors[index]}; border-radius: 2px;"></span>
            <span style="font-size: 14px; color: var(--text);">${item.label}</span>
        `;
        legendContainer.appendChild(legendItem);
    });
    
    // Success rate bar chart
    const successCtx = document.getElementById('successChart').getContext('2d');
    const successData = <?php echo json_encode($successByLevel); ?>;
    
    const successChart = new Chart(successCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(successData),
            datasets: [{
                data: Object.values(successData),
                backgroundColor: '#f59e0b',
                borderRadius: 8,
                barThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + '%';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        },
                        color: '#6b7280',
                        font: {
                            size: 12
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        color: '#6b7280',
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    }
                }
            }
        }
    });
});
</script>

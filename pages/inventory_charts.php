<?php
session_start();
include("../includes/db.php");
include("../includes/functions.php");

$user_data = check_login($conn);
if ($user_data['role'] !== 'admin') {
    header("Location: login.php");
    die;
}

function get_inventory_data($conn) {
    $query = "SELECT m.descripcion, COALESCE(SUM(il.cantidad), 0) as cantidad
              FROM materials m
              LEFT JOIN inventory_locations il ON m.id = il.material_id
              GROUP BY m.id";
    $result = $conn->query($query);
    if (!$result) {
        die("Error al obtener datos del inventario: " . $conn->error);
    }
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $result->close();
    return $data;
}

function get_movements_data($conn) {
    $query = "SELECT tipo_movimiento, SUM(cantidad) as total_cantidad
              FROM inventory_movements
              GROUP BY tipo_movimiento";
    $result = $conn->query($query);
    if (!$result) {
        die("Error al obtener datos de movimientos: " . $conn->error);
    }
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $result->close();
    return $data;
}

$inventory_data = get_inventory_data($conn);
$movements_data = get_movements_data($conn);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Dashboard de Inventario</title>
    <style>
        body {
            background-color: #121212;
            color: #e0e0e0;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .chart-container {
            position: relative;
            height: 400px;
            margin: 20px 0;
        }
        .card {
            margin: 20px 0;
            border: none;
            border-radius: 8px;
            background-color: #1f1f1f;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        .card-header {
            background-color: #333;
            color: #fff;
            font-size: 1.25rem;
            padding: 15px 20px;
            border-bottom: none;
            border-radius: 8px 8px 0 0;
        }
        .card-body {
            padding: 20px;
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2rem;
            color: #fff;
        }
        .btn-custom {
            background-color: #6200ea;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-custom:hover {
            background-color: #3700b3;
        }
        .tooltip {
            font-size: 0.9rem;
            background-color: #333;
            color: #fff;
            border-radius: 5px;
            padding: 5px;
        }
        footer {
            text-align: center;
            padding: 10px 0;
            background-color: #333;
            color: white;
            width: 100%;
            margin-top: 20px;
            /* Posicionar el footer al final del contenido, pero no fijo al fondo de la página */
        }
    </style>
</head>
<body>
    <?php include("../templates/header.php"); ?>
    <div class="container">
        <h2>Dashboard de Inventario</h2>
        <button class="btn-custom mb-4">Actualizar Datos</button>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Cantidad de Materiales
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="inventoryBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Proporción de Materiales
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="inventoryPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Tendencias de Inventario
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="inventoryLineChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Movimientos de Inventario
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="inventoryRadarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include("../templates/footer.php"); ?>

    <script>
        var inventoryLabels = <?php echo json_encode(array_column($inventory_data, 'descripcion')); ?>;
        var inventoryData = <?php echo json_encode(array_column($inventory_data, 'cantidad')); ?>;

        var barChartData = {
            labels: inventoryLabels,
            datasets: [{
                label: 'Cantidad de Material',
                data: inventoryData,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        };

        var barChartConfig = {
            type: 'bar',
            data: barChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    tooltip: {
                        backgroundColor: '#333',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#6200ea',
                        borderWidth: 1
                    }
                }
            }
        };

        var barCtx = document.getElementById('inventoryBarChart').getContext('2d');
        new Chart(barCtx, barChartConfig);

        var pieChartData = {
            labels: inventoryLabels,
            datasets: [{
                label: 'Proporción de Material',
                data: inventoryData,
                backgroundColor: [
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(255, 205, 86, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 205, 86, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        };

        var pieChartConfig = {
            type: 'pie',
            data: pieChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#e0e0e0'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#333',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#6200ea',
                        borderWidth: 1
                    }
                }
            }
        };

        var pieCtx = document.getElementById('inventoryPieChart').getContext('2d');
        new Chart(pieCtx, pieChartConfig);

        var lineChartData = {
            labels: inventoryLabels,
            datasets: [{
                label: 'Cantidad de Material a lo Largo del Tiempo',
                data: inventoryData,
                fill: false,
                borderColor: 'rgba(75, 192, 192, 1)',
                tension: 0.1
            }]
        };

        var lineChartConfig = {
            type: 'line',
            data: lineChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#e0e0e0'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#e0e0e0'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        backgroundColor: '#333',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#6200ea',
                        borderWidth: 1
                    }
                }
            }
        };

        var lineCtx = document.getElementById('inventoryLineChart').getContext('2d');
        new Chart(lineCtx, lineChartConfig);

        var radarChartData = {
            labels: inventoryLabels,
            datasets: [{
                label: 'Movimientos de Inventario',
                data: inventoryData,
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(255, 99, 132, 1)'
            }]
        };

        var radarChartConfig = {
            type: 'radar',
            data: radarChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        pointLabels: {
                            color: '#e0e0e0'
                        },
                        grid: {
                            color: '#444'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        backgroundColor: '#333',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#6200ea',
                        borderWidth: 1
                    }
                }
            }
        };

        var radarCtx = document.getElementById('inventoryRadarChart').getContext('2d');
        new Chart(radarCtx, radarChartConfig);
    </script>
</body>
</html>

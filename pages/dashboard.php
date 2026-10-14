<?php
require_once __DIR__ . '/../config/session.php';
require_login();

$conn = db();

$jumlahTransaksi = 0;
$resCount = $conn->query('SELECT COUNT(*) AS cnt FROM transaksi');
if ($resCount && $row = $resCount->fetch_assoc()) {
    $jumlahTransaksi = (int)$row['cnt'];
}

$totalPendapatan = 0.0;
$resSum = $conn->query("SELECT COALESCE(SUM(total), 0) AS total_sum FROM transaksi WHERE status IN ('selesai', 'diambil')");
if ($resSum && $row = $resSum->fetch_assoc()) {
    $totalPendapatan = (float)$row['total_sum'];
}

$labelsChart = [];
$revenueChart = [];
$countChart = [];
$resChart = $conn->query("
    SELECT DATE(tanggal) AS tgl, COUNT(*) AS jumlah_transaksi, COALESCE(SUM(total), 0) AS total_pendapatan
    FROM transaksi
    WHERE status IN ('selesai', 'diambil')
    GROUP BY DATE(tanggal)
    ORDER BY tgl DESC
    LIMIT 7
");
if ($resChart) {
    while ($row = $resChart->fetch_assoc()) {
        $labelsChart[] = $row['tgl'];
        $countChart[] = (int)$row['jumlah_transaksi'];
        $revenueChart[] = (float)$row['total_pendapatan'];
    }
}
$labelsChart = array_reverse($labelsChart);
$countChart = array_reverse($countChart);
$revenueChart = array_reverse($revenueChart);

$statusLabels = [];
$statusCounts = [];
$resStatus = $conn->query('SELECT status, COUNT(*) AS cnt FROM transaksi GROUP BY status');
if ($resStatus) {
    while ($row = $resStatus->fetch_assoc()) {
        $statusLabels[] = $row['status'];
        $statusCounts[] = (int)$row['cnt'];
    }
}

$recentTransaksi = [];
$resRecent = $conn->query("
    SELECT t.id_transaksi, t.tanggal, t.total, t.status, p.nama_pelanggan, u.nama_user
    FROM transaksi t
    JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan
    JOIN `user` u ON u.id_user = t.id_user
    ORDER BY t.id_transaksi DESC
    LIMIT 5
");
if ($resRecent) {
    while ($row = $resRecent->fetch_assoc()) {
        $recentTransaksi[] = $row;
    }
}
$conn->close();

$pageTitle = 'Dashboard';
$pageSubtitle = 'Ringkasan transaksi dan pendapatan.';
ob_start();
?>
<a href="transaksi.php" class="btn btn-outline-primary">Lihat transaksi</a>
<?php
$pageActions = ob_get_clean();
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>';
require __DIR__ . '/_layout_start.php';
?>

<div class="stat-grid">
    <div class="card">
        <div class="card-body stat-card">
            <div class="stat-icon">TX</div>
            <div>
                <span>Jumlah transaksi</span>
                <h2><?= $jumlahTransaksi ?></h2>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body stat-card">
            <div class="stat-icon is-green">Rp</div>
            <div>
                <span>Pendapatan selesai / diambil</span>
                <h2><?= rupiah($totalPendapatan) ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Pendapatan 7 hari terakhir</h6>
                <div id="chartRevenuePlaceholder" class="chart-empty d-none">Belum ada data pendapatan.</div>
                <div class="chart-box">
                    <canvas id="chartRevenue"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Distribusi status</h6>
                <div id="chartStatusPlaceholder" class="chart-empty d-none">Belum ada transaksi.</div>
                <div class="chart-box is-doughnut">
                    <canvas id="chartStatus"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Transaksi terbaru</h6>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Petugas</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$recentTransaksi): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Belum ada transaksi.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentTransaksi as $t): ?>
                        <tr>
                            <td><?= (int)$t['id_transaksi'] ?></td>
                            <td><?= e(date('d/m/Y H:i', strtotime($t['tanggal']))) ?></td>
                            <td><?= e($t['nama_pelanggan']) ?></td>
                            <td><?= e($t['nama_user']) ?></td>
                            <td><?= rupiah((float)$t['total']) ?></td>
                            <td><span class="<?= e(transaksi_status_badge_class($t['status'])) ?>"><?= e($t['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$pageScripts = '<script>
const labelsChart = ' . json_encode($labelsChart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';
const revenueChart = ' . json_encode($revenueChart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';
const countChart = ' . json_encode($countChart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';
const statusLabels = ' . json_encode($statusLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';
const statusCounts = ' . json_encode($statusCounts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';
const sum = (arr) => Array.isArray(arr) ? arr.reduce((a, v) => a + (Number(v) || 0), 0) : 0;

const ctxRevenue = document.getElementById("chartRevenue");
if (ctxRevenue) {
  const placeholder = document.getElementById("chartRevenuePlaceholder");
  if (!(labelsChart.length && sum(revenueChart) > 0)) {
    placeholder.classList.remove("d-none");
    ctxRevenue.parentElement.classList.add("d-none");
  } else {
    new Chart(ctxRevenue, {
      type: "bar",
      data: {
        labels: labelsChart,
        datasets: [
          { label: "Pendapatan", data: revenueChart, backgroundColor: "rgba(22,50,79,.25)", borderColor: "#16324f", borderWidth: 2, yAxisID: "yRevenue" },
          { type: "line", label: "Jumlah", data: countChart, borderColor: "#2f9e6b", tension: 0.25, yAxisID: "yCount" }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: "bottom" } },
        scales: {
          yRevenue: { beginAtZero: true, ticks: { callback: (v) => "Rp " + Number(v).toLocaleString("id-ID") } },
          yCount: { beginAtZero: true, position: "right", grid: { drawOnChartArea: false } }
        }
      }
    });
  }
}

const ctxStatus = document.getElementById("chartStatus");
if (ctxStatus) {
  const placeholder = document.getElementById("chartStatusPlaceholder");
  if (!(sum(statusCounts) > 0)) {
    placeholder.classList.remove("d-none");
    ctxStatus.parentElement.classList.add("d-none");
  } else {
    new Chart(ctxStatus, {
      type: "doughnut",
      data: {
        labels: statusLabels,
        datasets: [{ data: statusCounts, backgroundColor: ["#c9a227","#3d7ea6","#2f9e6b","#16324f"], borderWidth: 0 }]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom" } } }
    });
  }
}
</script>';
require __DIR__ . '/_layout_end.php';

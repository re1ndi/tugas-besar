<?php
// pages/laporan_tunggakan.php
session_start();
require_once '_template_sidebar.php'; 

if ($role !== 'admin') { header("Location: dashboard_admin.php"); exit(); }

// FUNGSI UNTUK MENGAMBIL CONFIG (Biaya Program)
function getConfig($key) {
    global $conn;
    $q = $conn->query("SELECT total_biaya FROM program_biaya WHERE program_pendidikan='$key'");
    return $q->fetch_assoc()['total_biaya'] ?? 0.00;
}

// --- QUERY UTAMA: MENGAMBIL SEMUA TUNGGAKAN ---
$sql = "
SELECT
    t.nis,
    t.nama_lengkap,
    t.program_pendidikan,
    t.angkatan,
    pb.total_biaya AS cost_total,
    COALESCE(SUM(p.jumlah), 0) AS total_paid,
    (pb.total_biaya - COALESCE(SUM(p.jumlah), 0)) AS remaining_balance
FROM trainee t
JOIN program_biaya pb ON t.program_pendidikan = pb.program_pendidikan
LEFT JOIN pembayaran p ON t.trainee_id = p.trainee_id AND p.status = 'VERIFIED'
WHERE t.status_pendaftaran != 'Nonaktif'
GROUP BY t.trainee_id, t.nis, t.nama_lengkap, t.program_pendidikan, t.angkatan, pb.total_biaya
ORDER BY remaining_balance DESC, t.angkatan DESC
";

$result = $conn->query($sql);

// VARIABEL UNTUK TOTAL GLOBAL
$grand_total_biaya = 0;
$grand_total_dibayar = 0;
$grand_total_tunggakan = 0;
?>

<h2 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-file-text-fill me-2 text-primary"></i> Laporan Sisa Tunggakan Siswa</h2>
<p class="lead mb-4">Daftar semua siswa aktif beserta sisa tagihan pembayaran program mereka.</p>

<div class="card shadow-sm border-0 mb-5">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle small">
                <thead class="table-danger">
                    <tr>
                        <th class="ps-4">NIS</th>
                        <th>Nama Siswa</th>
                        <th>Program</th>
                        <th>Total Biaya (Rp)</th>
                        <th>Total Dibayar (Rp)</th>
                        <th>SISA TUNGGAKAN (Rp)</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): 
                        $remaining = $row['remaining_balance'];
                        $is_lunas = $remaining <= 0;
                        $status_tagihan = $is_lunas ? 'LUNAS' : 'BELUM LUNAS';
                        $bg_tagihan = $is_lunas ? 'bg-success' : 'bg-danger';
                        $text_color = $is_lunas ? 'text-success' : 'text-danger';
                        
                        // Akumulasi Total Global
                        $grand_total_biaya += $row['cost_total'];
                        $grand_total_dibayar += $row['total_paid'];
                        $grand_total_tunggakan += $remaining;
                    ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?php echo $row['nis']; ?></td>
                            <td><?php echo $row['nama_lengkap']; ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo $row['program_pendidikan']; ?></span></td>
                            <td>Rp <?php echo number_format($row['cost_total'], 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($row['total_paid'], 0, ',', '.'); ?></td>
                            <td class="fw-bold <?php echo $text_color; ?>">
                                Rp <?php echo number_format($remaining, 0, ',', '.'); ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?php echo $bg_tagihan; ?>"><?php echo $status_tagihan; ?></span>
                            </td>
                        </tr>
                    <?php endwhile; endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-dark fw-bold">
                        <td colspan="3" class="text-end">TOTAL KESELURUHAN:</td>
                        <td>Rp <?php echo number_format($grand_total_biaya, 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($grand_total_dibayar, 0, ',', '.'); ?></td>
                        <td class="<?php echo ($grand_total_tunggakan <= 0 ? 'text-success' : 'text-danger'); ?>">
                            Rp <?php echo number_format($grand_total_tunggakan, 0, ',', '.'); ?>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once '_template_footer.php'; ?>
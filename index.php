
<?php
session_start();

if (!isset($_SESSION["tabungan"])) {
    $_SESSION["tabungan"] = [];
    $_SESSION["target"] = 1000000;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $aksi = $_POST["aksi"] ?? "";

    if ($aksi === "tambah") {
        $jumlah = (int)($_POST["jumlah"] ?? 0);
        $tanggal = $_POST["tanggal"] ?? date("Y-m-d");
        $catatan = trim($_POST["catatan"] ?? "");

        if (
            $jumlah > 0 &&
            $jumlah <= 1000000000 &&
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) &&
            $tanggal >= "2000-01-01" &&
            $tanggal <= date("Y-m-d")
        ) {
            $_SESSION["tabungan"][] = [
                "id" => uniqid(),
                "jumlah" => $jumlah,
                "tanggal" => $tanggal,
                "catatan" => substr($catatan, 0, 100)
            ];
        }
    }

    if ($aksi === "target") {
        $target = (int)($_POST["target"] ?? 0);

        if ($target > 0 && $target <= 1000000000000) {
            $_SESSION["target"] = $target;
        }
    }

    if ($aksi === "hapus") {
        $id = $_POST["id"] ?? "";

        $_SESSION["tabungan"] = array_values(array_filter(
            $_SESSION["tabungan"],
            fn($item) => $item["id"] !== $id
        ));
    }

    header("Location: index.php");
    exit;
}

$riwayat = $_SESSION["tabungan"];
$target = $_SESSION["target"];
$total = 0;
$totalHariIni = 0;

foreach ($riwayat as $item) {
    $total += $item["jumlah"];

    if ($item["tanggal"] === date("Y-m-d")) {
        $totalHariIni += $item["jumlah"];
    }
}

$persen = min(100, ($total / $target) * 100);

usort($riwayat, fn($a, $b) =>
    strcmp($b["tanggal"], $a["tanggal"])
);

function rupiah($angka) {
    return "Rp" . number_format($angka, 0, ",", ".");
}

function aman($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tabungan Harianku</title>

<style>
*{box-sizing:border-box}
body{
    margin:0;font-family:Arial,sans-serif;
    background:#f3f6fb;color:#263248
}
header{
    padding:30px 20px 65px;color:white;
    background:linear-gradient(135deg,#087f5b,#20c997)
}
header h1{margin:0 0 10px;font-size:27px}
header p{margin:0;line-height:1.5}
main{max-width:750px;margin:-40px auto 30px;padding:0 15px}
.card{
    background:white;padding:22px;border-radius:18px;
    margin-bottom:18px;box-shadow:0 5px 20px #00000008
}
.label{font-size:13px;color:#718096}
.total{font-size:32px;font-weight:bold;color:#087f5b;margin:12px 0 20px}
.stats{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px}
.stat{background:#f0faf5;padding:14px;border-radius:12px}
.stat strong{display:block;margin-top:8px;overflow-wrap:anywhere}
.progress-bg{height:12px;background:#e5e7eb;border-radius:20px;overflow:hidden;margin:12px 0}
.progress{height:100%;background:#10b981;border-radius:20px}
h2{font-size:19px;margin:0 0 18px}
label{display:block;font-weight:bold;font-size:14px;margin:13px 0 7px}
input{
    width:100%;padding:12px;border:1px solid #d7dee8;
    border-radius:10px;font-size:15px
}
button{
    border:0;border-radius:10px;padding:12px;
    font-weight:bold;cursor:pointer
}
.primary{width:100%;margin-top:16px;background:#087f5b;color:white}
.history{
    display:flex;justify-content:space-between;align-items:center;
    gap:12px;padding:15px 0;border-bottom:1px solid #edf0f4
}
.history:last-child{border-bottom:0}
.name{font-size:14px;font-weight:bold;overflow-wrap:anywhere}
.date{font-size:12px;color:#718096;margin-top:6px}
.amount{color:#087f5b;font-weight:bold;font-size:14px}
.delete{margin-top:8px;background:#fff0f0;color:#d63939;font-size:12px}
.empty{text-align:center;color:#718096;padding:22px 0}
footer{text-align:center;color:#718096;font-size:12px;padding:15px}
@media(max-width:480px){
    header h1{font-size:23px}
    .card{padding:18px}
    .total{font-size:27px}
}
</style>
</head>

<body>

<header>
    <h1>💰 Tabungan Harianku</h1>
    <p>Menabung sedikit demi sedikit untuk meraih impian!</p>
</header>

<main>

<section class="card">
    <div class="label">TOTAL TABUNGAN</div>
    <div class="total"><?= rupiah($total) ?></div>

    <div class="stats">
        <div class="stat">
            <div class="label">🎯 Target Tabungan</div>
            <strong><?= rupiah($target) ?></strong>
        </div>
        <div class="stat">
            <div class="label">📅 Tabungan Hari Ini</div>
            <strong><?= rupiah($totalHariIni) ?></strong>
        </div>
    </div>

    <div class="label">Progres target tabungan</div>
    <div class="progress-bg">
        <div class="progress" style="width:<?= $persen ?>%"></div>
    </div>
    <div class="label"><?= number_format($persen,1,",",".") ?>% tercapai</div>
</section>

<section class="card">
    <h2>🎯 Atur Target</h2>
    <form method="post">
        <input type="hidden" name="aksi" value="target">
        <label>Target tabungan (Rp)</label>
        <input type="number" name="target" min="1" value="<?= $target ?>" required>
        <button class="primary" type="submit">Simpan Target</button>
    </form>
</section>

<section class="card">
    <h2>➕ Tambah Tabungan</h2>
    <form method="post">
        <input type="hidden" name="aksi" value="tambah">

        <label>Jumlah uang (Rp)</label>
        <input type="number" name="jumlah" min="1" max="1000000000"
               placeholder="Contoh: 10000" required>

        <label>Tanggal menabung</label>
        <input type="date" name="tanggal" value="<?= date("Y-m-d") ?>"
               min="2000-01-01" max="<?= date("Y-m-d") ?>" required>

        <label>Catatan</label>
        <input type="text" name="catatan" maxlength="100"
               placeholder="Contoh: Menabung dari uang saku">

        <button class="primary" type="submit">💚 Simpan Tabungan</button>
    </form>
</section>

<section class="card">
    <h2>📋 Riwayat Tabungan</h2>

    <?php if (count($riwayat) === 0): ?>
        <div class="empty">
            <div style="font-size:38px">🐷</div>
            <p>Belum ada tabungan.</p>
            <p>Yuk, mulai menabung hari ini!</p>
        </div>
    <?php else: ?>
        <?php foreach ($riwayat as $item): ?>
            <div class="history">
                <div>
                    <div class="name">
                        <?= aman($item["catatan"] ?: "Tabungan harian") ?>
                    </div>
                    <div class="date">
                        <?= date("d-m-Y", strtotime($item["tanggal"])) ?>
                    </div>
                </div>

                <div style="text-align:right">
                    <div class="amount">
                        +<?= rupiah($item["jumlah"]) ?>
                    </div>
                    <form method="post"
                          onsubmit="return confirm('Hapus catatan tabungan ini?')">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id" value="<?= aman($item["id"]) ?>">
                        <button class="delete" type="submit">🗑 Hapus</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

</main>

<footer>
    Tabungan Harianku © <?= date("Y") ?> • Sedikit demi sedikit menjadi bukit 🌱
</footer>

</body>
</html>

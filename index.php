<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'event_tracker');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get initial data
$event = $conn->query("SELECT * FROM events WHERE id = 1")->fetch_assoc();
if (!$event) {
    $conn->query("INSERT INTO events (id, event_name, event_date) VALUES (1, 'Data Kehadiran', CURDATE())");
    $event = ['event_name' => 'Data Kehadiran', 'event_date' => date('Y-m-d')];
}

$attendance = $conn->query("SELECT * FROM attendance WHERE event_id = 1")->fetch_assoc();
if (!$attendance) {
    $conn->query("INSERT INTO attendance (event_id, hadir, ijin_keluar) VALUES (1, 0, 0)");
    $attendance = ['hadir' => 0, 'ijin_keluar' => 0];
}

// Add counter column if it doesn't exist
$check = $conn->query("SHOW COLUMNS FROM altar_calls LIKE 'counter'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE altar_calls ADD COLUMN counter INT DEFAULT 0");
}

$altar_calls = $conn->query("SELECT * FROM altar_calls WHERE event_id = 1 ORDER BY urutan");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Data Ministry CC Coach</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #0a0c0f;
            background-image: radial-gradient(circle at 30% 20%, rgba(45, 55, 72, 0.3) 0%, transparent 30%),
                              radial-gradient(circle at 80% 70%, rgba(30, 41, 59, 0.4) 0%, transparent 35%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            color: #e2e8f0;
            padding: 24px;
            position: relative;
        }

        .orb {
            position: fixed;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(65, 105, 225, 0.15);
            filter: blur(80px);
            z-index: 0;
            pointer-events: none;
        }

        .orb-1 {
            top: -150px;
            right: -100px;
            background: rgba(0, 180, 216, 0.12);
        }

        .orb-2 {
            bottom: -150px;
            left: -100px;
            background: rgba(138, 43, 226, 0.1);
            width: 500px;
            height: 500px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }

        .header {
            margin-bottom: 60px;
            padding: 20px 0;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 500;
            letter-spacing: -0.02em;
            color: white;
            background: rgba(255, 255, 255, 0.03);
            padding: 8px 20px;
            border-radius: 100px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }

        .logo span {
            color: #7aa2f7;
            font-weight: 300;
            margin-left: 4px;
        }

        .date-badge {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 8px 24px;
            border-radius: 100px;
            font-size: 0.9rem;
            font-weight: 300;
            color: #a0aec0;
        }

        .hero h1 {
            font-size: 4.5rem;
            font-weight: 500;
            letter-spacing: -0.03em;
            line-height: 1.1;
            color: white;
            margin-bottom: 16px;
        }

        .hero h1 span {
            color: #7aa2f7;
            font-weight: 300;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 3rem;
            }
        }

        .glass-card {
            background: rgba(18, 22, 28, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(122, 162, 247, 0.15);
            border-radius: 32px;
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: all 0.3s ease;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 32px;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            background: rgba(122, 162, 247, 0.1);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            border: 1px solid rgba(122, 162, 247, 0.2);
        }

        .card-header h2 {
            font-size: 1.8rem;
            font-weight: 400;
            letter-spacing: -0.02em;
            color: white;
        }

        .card-header h2 span {
            font-size: 1rem;
            font-weight: 300;
            color: #7aa2f7;
            margin-left: 12px;
            background: rgba(122, 162, 247, 0.1);
            padding: 4px 12px;
            border-radius: 40px;
        }

        .counter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .counter-item {
            background: rgba(10, 14, 20, 0.5);
            border: 1px solid rgba(122, 162, 247, 0.1);
            border-radius: 24px;
            padding: 24px;
            transition: all 0.2s ease;
        }

        .counter-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #7aa2f7;
            margin-bottom: 12px;
            font-weight: 400;
        }

        .counter-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .counter-value {
            font-size: 3.5rem;
            font-weight: 300;
            color: white;
            line-height: 1;
        }

        .counter-actions {
            display: flex;
            gap: 8px;
        }

        .btn-counter {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            border: 1px solid rgba(122, 162, 247, 0.2);
            background: rgba(10, 14, 20, 0.6);
            color: #e2e8f0;
            font-size: 1.5rem;
            font-weight: 300;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }

        .btn-counter:hover {
            background: rgba(122, 162, 247, 0.2);
            border-color: rgba(122, 162, 247, 0.4);
            color: white;
            transform: scale(0.98);
        }

        .btn-counter:active {
            transform: scale(0.95);
        }

        .btn-counter.loading {
            opacity: 0.5;
            pointer-events: none;
        }

        .altar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin: 24px 0;
        }

        .altar-card {
            background: rgba(10, 14, 20, 0.4);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(122, 162, 247, 0.12);
            border-radius: 28px;
            padding: 28px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .altar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .altar-name {
            font-size: 1.4rem;
            font-weight: 400;
            letter-spacing: -0.02em;
            color: white;
        }

        .altar-value {
            font-size: 2.8rem;
            font-weight: 300;
            color: #7aa2f7;
            line-height: 1;
            text-align: center;
            padding: 10px 0;
        }

        .altar-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .altar-actions .btn-counter {
            width: 56px;
            height: 56px;
            border-radius: 20px;
            font-size: 1.8rem;
        }

        .add-altar-section {
            margin-top: 40px;
            padding-top: 32px;
            border-top: 1px solid rgba(122, 162, 247, 0.15);
        }

        .add-altar-form {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .input-altar {
            flex: 1;
            min-width: 280px;
            padding: 18px 24px;
            background: rgba(10, 14, 20, 0.6);
            border: 1px solid rgba(122, 162, 247, 0.15);
            border-radius: 24px;
            font-size: 1rem;
            color: white;
        }

        .input-altar:focus {
            outline: none;
            border-color: #7aa2f7;
        }

        .btn-primary {
            padding: 18px 36px;
            background: rgba(122, 162, 247, 0.1);
            border: 1px solid rgba(122, 162, 247, 0.3);
            border-radius: 24px;
            color: #7aa2f7;
            font-size: 0.95rem;
            font-weight: 500;
            letter-spacing: 0.02em;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-primary:hover {
            background: rgba(122, 162, 247, 0.2);
            border-color: #7aa2f7;
            color: white;
        }

        .btn-primary.loading {
            opacity: 0.5;
            pointer-events: none;
        }

        .empty-names {
            text-align: center;
            padding: 60px;
            color: #5a6a7a;
            font-weight: 300;
            border: 1px dashed rgba(122, 162, 247, 0.2);
            border-radius: 32px;
            background: rgba(0, 0, 0, 0.2);
        }

        .toast-message {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: rgba(122, 162, 247, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid #7aa2f7;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 0.9rem;
            z-index: 9999;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .footer {
            text-align: center;
            margin-top: 80px;
            padding: 32px 0;
            color: #4a5a6a;
            font-size: 0.9rem;
            letter-spacing: 0.1em;
            border-top: 1px solid rgba(122, 162, 247, 0.1);
        }

        @media (max-width: 768px) {
            .counter-main {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .counter-actions {
                width: 100%;
                justify-content: flex-end;
            }
            
            .add-altar-form {
                flex-direction: column;
            }
            
            .btn-primary {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="container">
        <div class="header">
            <div class="header-top">
                <div class="logo">
                    DATA MINISTRY <span>✦</span>
                </div>
                <div class="date-badge" id="current-date">
                    <?php echo date('l, j F Y', strtotime($event['event_date'])); ?>
                </div>
            </div>
            <div class="hero">
                <h1>Connecting Coach <span>Nipon</span></h1>
            </div>
        </div>

        <!-- Attendance Card -->
        <div class="glass-card">
            <div class="card-header">
                <h2>Data Kehadiran <span>Counters</span></h2>
            </div>
            
            <div class="counter-grid">
                <div class="counter-item">
                    <div class="counter-label">Tally Counter</div>
                    <div class="counter-main">
                        <span class="counter-value" id="hadir-value"><?php echo (int)$attendance['hadir']; ?></span>
                        <div class="counter-actions">
                            <button class="btn-counter update-attendance" data-type="hadir" data-action="minus">−</button>
                            <button class="btn-counter update-attendance" data-type="hadir" data-action="plus">+</button>
                        </div>
                    </div>
                </div>

                <div class="counter-item">
                    <div class="counter-label">Seat Counter</div>
                    <div class="counter-main">
                        <span class="counter-value" id="ijin-value"><?php echo (int)$attendance['ijin_keluar']; ?></span>
                        <div class="counter-actions">
                            <button class="btn-counter update-attendance" data-type="ijin" data-action="minus">−</button>
                            <button class="btn-counter update-attendance" data-type="ijin" data-action="plus">+</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Altar Calls Card -->
        <div class="glass-card">
            <div class="card-header">
                <h2>Altar Calls <span>Responses</span></h2>
            </div>
            
            <div id="altar-calls-container">
                <?php if ($altar_calls && $altar_calls->num_rows > 0): ?>
                    <div class="altar-grid">
                        <?php while($altar = $altar_calls->fetch_assoc()): 
                            $counter = $altar['counter'] ?? 0;
                        ?>
                            <div class="altar-card" id="altar-<?php echo $altar['id']; ?>">
                                <div class="altar-header">
                                    <span class="altar-name"><?php echo htmlspecialchars($altar['nama_altar_call']); ?></span>
                                </div>
                                
                                <div class="altar-value altar-counter-<?php echo $altar['id']; ?>"><?php echo (int)$counter; ?></div>

                                <div class="altar-actions">
                                    <button class="btn-counter update-altar" data-id="<?php echo $altar['id']; ?>" data-action="minus">−</button>
                                    <button class="btn-counter update-altar" data-id="<?php echo $altar['id']; ?>" data-action="plus">+</button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-names" id="empty-altar-state">Belum ada altar call. Tambahkan altar call pertama.</div>
                <?php endif; ?>
            </div>

            <div class="add-altar-section">
                <form id="add-altar-form" class="add-altar-form">
                    <input type="text" id="new-altar-name" class="input-altar" placeholder="Nama altar call" required>
                    <button type="submit" id="add-altar-btn" class="btn-primary">Tambah Altar Call</button>
                </form>
            </div>
        </div>

        <div class="footer">
            DATA MINISTRY
        </div>
    </div>

    <script>
    $(document).ready(function() {
        
        // Function to show toast message
        function showToast(message) {
            $('.toast-message').remove();
            const toast = $('<div class="toast-message">' + message + '</div>');
            $('body').append(toast);
            setTimeout(() => toast.fadeOut(300, function() { $(this).remove(); }), 2000);
        }

        // Update attendance with AJAX
        $('.update-attendance').click(function() {
            const btn = $(this);
            const type = btn.data('type');
            const action = btn.data('action');
            
            btn.addClass('loading');
            
            $.ajax({
                url: 'ajax_handler.php',
                type: 'POST',
                data: {
                    action: 'update_attendance',
                    type: type,
                    update: action
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#hadir-value').text(response.hadir);
                        $('#ijin-value').text(response.ijin);
                        
                        // Fixed notification text
                        let actionText = action === 'plus' ? 'ditambah' : 'dikurangi';
                        let typeText = type === 'hadir' ? 'Hadir' : 'Hadir (Akhir)';
                        showToast(typeText + ' ' + actionText);
                    }
                },
                error: function() {
                    showToast('Terjadi kesalahan');
                },
                complete: function() {
                    btn.removeClass('loading');
                }
            });
        });

        // Update altar call with AJAX
        $('.update-altar').click(function() {
            const btn = $(this);
            const id = btn.data('id');
            const action = btn.data('action');
            
            btn.addClass('loading');
            
            $.ajax({
                url: 'ajax_handler.php',
                type: 'POST',
                data: {
                    action: 'update_altar',
                    altar_id: id,
                    update: action
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('.altar-counter-' + id).text(response.counter);
                        showToast('Respon ' + (response.success ? 'ditambah' : 'dikurangi'));
                    }
                },
                error: function() {
                    showToast('Terjadi kesalahan');
                },
                complete: function() {
                    btn.removeClass('loading');
                }
            });
        });

        // Add new altar call with AJAX
        $('#add-altar-form').submit(function(e) {
            e.preventDefault();
            
            const btn = $('#add-altar-btn');
            const name = $('#new-altar-name').val().trim();
            
            if (!name) return;
            
            btn.addClass('loading');
            
            $.ajax({
                url: 'ajax_handler.php',
                type: 'POST',
                data: {
                    action: 'add_altar',
                    nama: name
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Remove empty state if exists
                        $('#empty-altar-state').remove();
                        
                        // Get container and append new altar card
                        const container = $('#altar-calls-container');
                        
                        // If no grid exists, create one
                        if (!$('.altar-grid').length) {
                            container.html('<div class="altar-grid"></div>');
                        }
                        
                        // Create new altar card HTML
                        const newCard = `
                            <div class="altar-card" id="altar-${response.id}">
                                <div class="altar-header">
                                    <span class="altar-name">${response.nama}</span>
                                </div>
                                <div class="altar-value altar-counter-${response.id}">0</div>
                                <div class="altar-actions">
                                    <button class="btn-counter update-altar" data-id="${response.id}" data-action="minus">−</button>
                                    <button class="btn-counter update-altar" data-id="${response.id}" data-action="plus">+</button>
                                </div>
                            </div>
                        `;
                        
                        $('.altar-grid').append(newCard);
                        $('#new-altar-name').val('');
                        showToast('Altar call ditambahkan');
                        
                        // Re-attach event handlers to new buttons
                        $('.update-altar').off('click').on('click', function() {
                            const btn = $(this);
                            const id = btn.data('id');
                            const action = btn.data('action');
                            
                            btn.addClass('loading');
                            
                            $.ajax({
                                url: 'ajax_handler.php',
                                type: 'POST',
                                data: {
                                    action: 'update_altar',
                                    altar_id: id,
                                    update: action
                                },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.success) {
                                        $('.altar-counter-' + id).text(response.counter);
                                        showToast('Respon ' + (action === 'plus' ? 'ditambah' : 'dikurangi'));
                                    }
                                },
                                complete: function() {
                                    btn.removeClass('loading');
                                }
                            });
                        });
                    }
                },
                error: function() {
                    showToast('Gagal menambah altar call');
                },
                complete: function() {
                    btn.removeClass('loading');
                }
            });
        });

        // Optional: Real-time sync every 5 seconds
        function syncData() {
            $.ajax({
                url: 'ajax_handler.php',
                type: 'POST',
                data: {
                    action: 'sync_data'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#hadir-value').text(response.hadir);
                        $('#ijin-value').text(response.ijin);
                        
                        // Update altar call values
                        $.each(response.altar_calls, function(id, counter) {
                            $('.altar-counter-' + id).text(counter);
                        });
                    }
                }
            });
        }
        
        // Sync every 10 seconds
        setInterval(syncData, 10000);
    });
    </script>
</body>
</html>
<?php $conn->close(); ?>
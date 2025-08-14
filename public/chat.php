<?php

declare(strict_types=1);

require __DIR__ . '/../db/Connection.php';
require __DIR__ . '/../db/schema.php';

session_start();
$__dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$BASE_PATH = rtrim($__dir, '/');
if ($BASE_PATH === '/' || $BASE_PATH === '.') $BASE_PATH = '';

if (!isset($_SESSION['user'])) {
    header('Location: ' . $BASE_PATH . '/login.php');
    exit;
}

require __DIR__ . '/partials/header.php';

$withId = filter_input(INPUT_GET, 'with', FILTER_VALIDATE_INT);
if (!$withId || $withId <= 0) {
    header('Location: ' . $BASE_PATH . '/messages.php');
    exit;
}

$pdo = Connection::get();
ensure_users_table($pdo);
ensure_messages_table($pdo);

$stmt = $pdo->prepare('SELECT id, username FROM users WHERE id=:id');
$stmt->execute([':id' => $withId]);
$peer = $stmt->fetch();
if (!$peer) {
    require __DIR__ . '/partials/footer.php';
    http_response_code(404);
    echo '<div class="alert">User not found.</div>';
    exit;
}

$csrf = bin2hex(random_bytes(16));
$_SESSION['csrf'] = $csrf;
?>
<div class="hero" style="padding-top:32px">
    <h1>Chat with: <?= h($peer['username']) ?></h1>
    <p class="badge">რეალური გაგზავნა/მიღება (polling API-ით).</p>
</div>

<div class="card chat-wrap">
    <div id="chatLog" class="chat-log"></div>
    <form id="chatForm" class="chat-input" autocomplete="off">
        <input type="hidden" id="peerId" value="<?= (int)$peer['id'] ?>">
        <input type="hidden" id="csrf" value="<?= h($csrf) ?>">
        <input class="input" id="messageInput" placeholder="Write a message…" maxlength="2000" />
        <button class="btn primary" type="submit">Send</button>
    </form>
</div>

<div class="form-actions" style="margin-top:12px;">
    <a class="btn" href="<?= h($BASE_PATH) ?>/messages.php">← Back to list</a>
</div>

<script>
    (function() {
        const BASE_PATH = <?= json_encode($BASE_PATH) ?>;
        const peerId = Number(document.getElementById('peerId').value);
        const csrf = document.getElementById('csrf').value;
        const log = document.getElementById('chatLog');
        const form = document.getElementById('chatForm');
        const input = document.getElementById('messageInput');

        let lastId = 0;
        let es = null;

        function escapeHtml(s) {
            const d = document.createElement('div');
            d.innerText = s;
            return d.innerHTML;
        }

        function appendMsg(side, text, id, pending = false) {
            const div = document.createElement('div');
            div.className = 'chat-msg ' + (side === 'me' ? 'me' : 'you') + (pending ? ' pending' : '');
            if (id) {
                div.dataset.id = String(id);
                if (id > lastId) lastId = id;
            }
            div.innerHTML = '<div class="bubble">' + escapeHtml(text) + '</div>';
            log.appendChild(div);
            log.scrollTop = log.scrollHeight;
            return div;
        }

        function hasMsgId(id) {
            return !!log.querySelector('.chat-msg[data-id="' + id + '"]');
        }

        function connectSSE() {
            if (es) {
                try {
                    es.close();
                } catch {}
            }
            const url = BASE_PATH + '/api/messages/sse.php?with=' + encodeURIComponent(peerId) + (lastId ? '&sinceId=' + lastId : '');
            es = new EventSource(url);

            es.onmessage = (e) => {
                try {
                    const m = JSON.parse(e.data);
                    if (!m || !m.id) return;
                    if (!hasMsgId(m.id)) appendMsg(m.side, m.body, m.id);
                } catch {}
            };

            es.addEventListener('ping', () => {
});

            es.onerror = () => {
                try {
                    es.close();
                } catch {}
                setTimeout(connectSSE, 1500);
            };
        }

        async function send(text) {
            const pending = appendMsg('me', text, null, true);
            try {
                const res = await fetch(BASE_PATH + '/api/messages/send.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        with: peerId,
                        body: text,
                        csrf
                    })
                });
                if (!res.ok) {
                    pending.classList.add('error');
                    const err = await res.json().catch(() => ({
                        error: 'unknown'
                    }));
                    alert('Send failed: ' + (err.detail || err.error || res.status));
                    return;
                }
                const data = await res.json();
                if (data && data.id) {
                    pending.dataset.id = String(data.id);
                    pending.classList.remove('pending');
                    if (data.id > lastId) lastId = data.id;
                }
             } catch {
                pending.classList.add('error');
                alert('Network error while sending');
            }
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const text = (input.value || '').trim();
            if (!text) return;
            input.value = '';
            send(text);
        });

        connectSSE();
        window.addEventListener('beforeunload', () => {
            try {
                es?.close();
            } catch {}
        });
    })();
</script>


<?php require __DIR__ . '/partials/footer.php'; ?>
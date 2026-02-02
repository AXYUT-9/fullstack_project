document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('live-search');
    if (!input) return;

    let timeout;
    input.addEventListener('input', () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            const q = input.value.trim();
            if (q.length < 2) {
                location.reload();
                return;
            }

            fetch('search.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'q=' + encodeURIComponent(q)
            })
            .then(r => r.text())
            .then(html => {
                document.getElementById('results').innerHTML = html;
            })
            .catch(err => console.log('AJAX error:', err));
        }, 400);
    });
});
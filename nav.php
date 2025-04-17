<nav class="main-nav">
    <div class="links">
        <a href="discovery.php">🔍 Discovery</a>
        <a href="devices.php">🖨️ Devices</a>
    </div>
    <div class="toggle-theme" onclick="toggleTheme()" title="Toggle Dark Mode">🌞</div>
</nav>

<script>
function toggleTheme() {
    const body = document.body;
    body.classList.toggle('dark');
    const icon = document.querySelector('.toggle-theme');
    const isDark = body.classList.contains('dark');
    icon.textContent = isDark ? '🌙' : '🌞';
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

(function loadTheme() {
    const theme = localStorage.getItem('theme');
    if (theme === 'dark') {
        document.body.classList.add('dark');
        document.querySelector('.toggle-theme').textContent = '🌙';
    }
})();
</script>

import { spawn, execSync } from 'child_process';

function startMariaDBIfNeeded() {
  try {
    execSync('mysqladmin ping', { stdio: 'ignore' });
    console.log('MariaDB is already running.');
  } catch (e) {
    console.log('Starting MariaDB daemon...');
    try {
      execSync('mkdir -p /var/run/mysqld && chown -R mysql:mysql /var/run/mysqld 2>/dev/null || true');
      const mariadb = spawn('mariadbd', ['--user=root'], {
        detached: true,
        stdio: 'ignore'
      });
      mariadb.unref();

      for (let i = 0; i < 10; i++) {
        try {
          execSync('mysqladmin ping', { stdio: 'ignore' });
          console.log('MariaDB is alive and ready.');
          try {
            execSync("mysql -u root -e \"ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING ''; GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION; CREATE USER IF NOT EXISTS 'root'@'127.0.0.1'; GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1'; FLUSH PRIVILEGES;\"", { stdio: 'ignore' });
          } catch (_) {}
          break;
        } catch (_) {
          execSync('sleep 0.5');
        }
      }
    } catch (err) {
      console.warn('Could not launch mariadbd directly:', err.message);
    }
  }
}

startMariaDBIfNeeded();

const port = process.env.PORT || 3000;
console.log(`Starting PHP server on port ${port}...`);

const php = spawn('php', ['-S', `0.0.0.0:${port}`, 'router.php'], {
  stdio: 'inherit'
});

php.on('error', (err) => {
  console.error('Failed to start PHP server:', err);
  process.exit(1);
});

php.on('close', (code) => {
  console.log(`PHP server exited with code ${code}`);
  process.exit(code || 0);
});

process.on('SIGINT', () => {
  php.kill('SIGINT');
  process.exit(0);
});

process.on('SIGTERM', () => {
  php.kill('SIGTERM');
  process.exit(0);
});

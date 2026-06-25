import asyncore
import smtpd
from datetime import datetime
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
LOG_DIR = ROOT / 'logs'
INBOX_LOG = LOG_DIR / 'dev_inbox.log'
MAIL_DIR = LOG_DIR / 'emails'

LOG_DIR.mkdir(parents=True, exist_ok=True)
MAIL_DIR.mkdir(parents=True, exist_ok=True)


class CaptureServer(smtpd.SMTPServer):
    def process_message(self, peer, mailfrom, rcpttos, data, **kwargs):
        ts = datetime.now().strftime('%Y%m%d_%H%M%S_%f')
        eml_path = MAIL_DIR / f'{ts}.eml'

        with eml_path.open('wb') as f:
            if isinstance(data, str):
                data = data.encode('utf-8', errors='replace')
            f.write(data)

        with INBOX_LOG.open('a', encoding='utf-8') as f:
            f.write(f'[{datetime.now().isoformat(sep=" ", timespec="seconds")}] FROM={mailfrom} TO={";".join(rcpttos)} FILE={eml_path.name}\n')

        print(f'Captured email -> {eml_path.name}')
        return


if __name__ == '__main__':
    server = CaptureServer(('127.0.0.1', 1025), None)
    print('Dev SMTP server listening on 127.0.0.1:1025')
    asyncore.loop()

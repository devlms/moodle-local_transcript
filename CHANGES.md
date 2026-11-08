# Changes

## 1.0.0 (2026-11-08)

First stable release.

- Deleting a student's account revokes their transcripts, and the verify page treats any transcript of a deleted account as revoked.
- A revoked or altered transcript shows its status only, never the student's name or grades, and cannot be downloaded as a PDF.
- Grades hidden in the gradebook, and grades in courses that hide them from students, stay off transcripts.
- The signing secret can live in config.php as $CFG->local_transcript_secret. A missing secret is an error once transcripts exist, instead of a new secret that would mark every transcript as altered.
- PDFs use a font that covers Arabic, Cyrillic and other non-Latin names.
- The manage list leaves out deleted accounts.
- Transcripts issued before the plain-text names change in 0.1.0 may show "&amp;" in names; issue a new one to fix it.
- CI covers Moodle 4.5, 5.0, 5.1, 5.2 and 5.3 on PostgreSQL and MariaDB.

## 0.1.0 (2026-11-06)

First alpha.

# Spam Checker (PHP)

A small, dependency-free PHP spam checker project with:

- clean web UI
- spam scoring engine
- keyword/rule-based detection
- optional Bayesian-style learning from user feedback
- JSON API endpoint
- automatic persistence
  - SQLite when `pdo_sqlite` is available
  - JSON file fallback when SQLite is unavailable
- ready-to-push GitHub structure

## Requirements

- PHP 8.1+
- SQLite is optional (recommended)

## Project Structure

```text
spam-checker/
├── public/
│   ├── index.php
│   ├── api.php
│   └── style.css
├── src/
│   ├── Config.php
│   ├── Database.php
│   ├── TrainingRepository.php
│   ├── SpamAnalyzer.php
│   └── helpers.php
├── data/
│   └── .gitkeep
├── scripts/
│   └── init_db.php
├── .gitignore
├── LICENSE
└── README.md
```

## Run Locally

From the project root:

```bash
php scripts/init_db.php
php -S localhost:8000 -t public
```

Open:

```text
http://localhost:8000
```

## Storage Behavior

The app stores training feedback automatically.

- If `pdo_sqlite` is available, it uses SQLite.
- Otherwise, it falls back to `data/training_samples.json`.

That makes the project easier to run on systems where SQLite support is not enabled yet.

## How It Works

The app calculates a spam score using:

1. suspicious keyword detection
2. URL / money / urgency patterns
3. excessive uppercase / punctuation signals
4. Bayesian token probabilities from stored training examples

The final result is classified as:

- `Spam`
- `Likely Spam`
- `Needs Review`
- `Likely Safe`

## API Usage

### Analyze text

**POST** `/api.php?action=analyze`

JSON body:

```json
{
  "message": "Congratulations! You won a free prize. Click now!"
}
```

Example using curl:

```bash
curl -X POST http://localhost:8000/api.php?action=analyze \
  -H "Content-Type: application/json" \
  -d '{"message":"Congratulations! You won a free prize. Click now!"}'
```

### Save feedback / train the model

**POST** `/api.php?action=feedback`

JSON body:

```json
{
  "message": "Your payment is pending, click here",
  "label": "spam"
}
```

Allowed labels:

- `spam`
- `ham`

## Notes

- This is intentionally lightweight and easy to understand.
- It is suitable for learning, demos, internship assignments, and starter projects.
- For production-grade filtering, combine this with sender reputation, header analysis, DKIM/SPF checks, and a larger trained model.

## Next Improvements

- user authentication for admin-only training
- bulk CSV upload for datasets
- confidence charts
- rate limiting
- Docker setup
- PHPUnit tests

## License

`MIT`

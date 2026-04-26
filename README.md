# coWork
Демонстрація роботи з базою даних на прикладі сайту з бронюванням коворкінгів

## Stress test

Run the CLI load test:

```bash
php scripts/stress_test.php --virtual-users=200 --requests-per-user=50 --seed-users=2000 --bookings-per-user=12 --reviews-per-user=2
```

Flags:

- `--seed-only` to create large test data without running timings
- `--cleanup` to remove stress-test users and related rows
- `--prefix=my_run` to isolate one stress dataset from another

The script expands `users`, `bookings`, `booking_slots`, and `reviews`, then measures timing for read and write-heavy query scenarios.

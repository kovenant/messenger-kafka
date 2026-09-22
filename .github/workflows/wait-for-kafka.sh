#! /bin/bash
end=$((SECONDS+60))

while [ $SECONDS -lt $end ]; do
    if nc -z -w 1 localhost 9092; then
        exit 0
    fi
    sleep 1
done

echo "Kafka did not become ready within 60 seconds" >&2
exit 1

#!/bin/bash

# chmod +x check-port.sh
#./check-port.sh 8080 --> check port 8080

PORT=${1:-8080}

echo "Checking port $PORT ..."

result=$(lsof -nP -iTCP:$PORT -sTCP:LISTEN)

if [ -z "$result" ]; then
  echo "Port $PORT is currently FREE (not in use)."
else
  echo "Port $PORT is currently in USE by:"
  echo "$result"
fi

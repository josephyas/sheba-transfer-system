#!/bin/bash
echo "Running performance test..."
docker run --rm -v $(pwd)/k6-test.js:/test.js --network=host grafana/k6 run /test.js

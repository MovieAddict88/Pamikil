#!/bin/bash
BLUE_PNG='iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
for size in 72 96 128 144 152 192 384 512; do
  echo "$BLUE_PNG" | base64 -d > icon-${size}.png
  echo "Created icon-${size}.png"
done
echo "$BLUE_PNG" | base64 -d > screenshot1.png
echo "Created screenshot1.png"

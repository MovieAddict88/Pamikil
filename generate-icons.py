#!/usr/bin/env python3
from PIL import Image, ImageDraw, ImageFont
import os

# Create assets/images directory if it doesn't exist
os.makedirs('assets/images', exist_ok=True)

# Define icon sizes
sizes = [72, 96, 128, 144, 152, 192, 384, 512]
color = (229, 9, 20)  # Red color

# Generate icons
for size in sizes:
    # Create a new image with transparent background
    img = Image.new('RGBA', (size, size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    
    # Draw a circle
    margin = 5
    draw.ellipse([margin, margin, size-margin, size-margin], fill=color)
    
    # Draw text "C" in the center
    try:
        font_size = int(size * 0.5)
        font = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", font_size)
    except:
        font = ImageFont.load_default()
    
    text = "C"
    bbox = draw.textbbox((0, 0), text, font=font)
    text_width = bbox[2] - bbox[0]
    text_height = bbox[3] - bbox[1]
    position = ((size - text_width) / 2, (size - text_height) / 2 - bbox[1])
    
    draw.text(position, text, fill=(255, 255, 255), font=font)
    
    # Save the image
    img.save(f'assets/images/icon-{size}.png', 'PNG')
    print(f'Generated icon-{size}.png')

# Generate placeholder image
placeholder = Image.new('RGB', (300, 450), (26, 26, 26))
draw = ImageDraw.Draw(placeholder)

try:
    font = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", 24)
except:
    font = ImageFont.load_default()

text = "No Image"
bbox = draw.textbbox((0, 0), text, font=font)
text_width = bbox[2] - bbox[0]
position = ((300 - text_width) / 2, 225)
draw.text(position, text, fill=(140, 140, 140), font=font)

placeholder.save('assets/images/placeholder.jpg', 'JPEG')
print('Generated placeholder.jpg')

# Generate screenshot placeholders
screenshot1 = Image.new('RGB', (1280, 720), (15, 15, 15))
draw1 = ImageDraw.Draw(screenshot1)
try:
    font = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", 48)
except:
    font = ImageFont.load_default()

text = "CineCraze Screenshot"
bbox = draw1.textbbox((0, 0), text, font=font)
text_width = bbox[2] - bbox[0]
position = ((1280 - text_width) / 2, 360)
draw1.text(position, text, fill=color, font=font)
screenshot1.save('assets/images/screenshot1.png', 'PNG')
print('Generated screenshot1.png')

screenshot2 = Image.new('RGB', (1280, 720), (26, 26, 26))
draw2 = ImageDraw.Draw(screenshot2)
draw2.text(position, text, fill=(0, 212, 255), font=font)
screenshot2.save('assets/images/screenshot2.png', 'PNG')
print('Generated screenshot2.png')

print('\nAll icons and images generated successfully!')

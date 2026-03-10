# 🖨️ Hospital Scanner Archiving System

A **100% free** document scanning solution for hospital surgery referral letters, connecting directly to scanner hardware via a local Python bridge.

---

## 📋 System Overview

This system allows hospital reception staff to scan documents directly from a connected scanner with **one button press**, without manual file selection or uploads.

### Architecture

```
Browser (Surgery Form)
    ↓ HTTP POST
Python Flask Server (localhost:37426)
    ↓ CLI Command
NAPS2 Console (Scanner Driver)
    ↓ Hardware Interface
Physical Scanner Device
```

---

## ✅ Features

- ✨ **One-Click Scanning**: Single button activates scanner automatically
- 🆓 **100% Free**: No paid software required
- 🚀 **Direct Hardware Access**: Communicates with scanner via NAPS2
- 📸 **Automatic Preview**: Shows scanned image immediately
- 🌐 **Simple Integration**: Standard HTTP POST from JavaScript
- 🔒 **Local Only**: No cloud services, data stays on-premise

---

## 🔧 Technical Requirements

| Component | Version | Purpose |
|-----------|---------|---------|
| **Python** | 3.8+ | Runs the bridge server |
| **Flask** | Latest | Handles HTTP requests |
| **Flask-CORS** | Latest | Allows browser connections |
| **NAPS2** | 7.x+ | Scanner communication |
| **Scanner** | Any TWAIN/WIA | Hardware device |

---

## 📦 Installation Steps

### 1. Install Python

Download from: https://www.python.org/downloads/

**⚠️ Important**: Check "Add Python to PATH" during installation

### 2. Install Dependencies

```bash
cd C:\wamp64\www\hospital-system
python -m pip install flask flask-cors
```

### 3. Install NAPS2

Download from: https://github.com/cyanfish/naps2/releases/latest

Install to default location: `C:\Program Files\NAPS2\`

### 4. Connect Scanner

- Plug in your scanner via USB
- Install scanner drivers if needed
- Test in NAPS2 application first

---

## 🚀 Running the System

### Quick Start (Arabic Users)

Double-click: `تشغيل_برنامج_الارشفة.bat`

### Manual Start

```bash
python scanner_bridge.py
```

You should see:
```
═══════════════════════════════════════════════════
  🖨️  برنامج الأرشفة - Hospital Scanner Bridge
═══════════════════════════════════════════════════

✓ الخادم يعمل على: http://localhost:37426
✓ جاهز لاستقبال طلبات المسح من الموقع

💡 لإيقاف البرنامج: اضغط Ctrl+C
═══════════════════════════════════════════════════
```

**⚠️ Keep this window open while scanning!**

---

## 🌐 API Endpoints

### `POST /scan`

Triggers scanner and returns scanned image.

**Response**: `image/jpeg` blob

**Error Codes**:
- `404`: NAPS2 not installed
- `408`: Scan timeout (no paper or scanner offline)
- `500`: General scanning error

### `GET /status`

Checks if scanner system is ready.

**Response**: 
```json
{
  "status": "ready",
  "message": "جاهز للمسح"
}
```

---

## 💻 Frontend Integration

The system is already integrated in `resources/views/surgeries/create.blade.php`:

```javascript
// Simplified scanning function
async function scanViaLocalBridge() {
    const response = await fetch('http://localhost:37426/scan', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    });
    
    const blob = await response.blob();
    handleScannedImage(blob);
}
```

---

## 🗂️ File Structure

```
hospital-system/
├── scanner_bridge.py              # Python Flask server
├── تشغيل_برنامج_الارشفة.bat      # Windows launcher script
├── دليل_التثبيت_الشامل.txt        # Complete setup guide (Arabic)
├── اقرأني_أولاً.txt               # Quick start guide (Arabic)
└── resources/views/surgeries/
    └── create.blade.php           # Surgery form with scanner integration
```

---

## 🐛 Troubleshooting

### "Failed to fetch" Error

**Problem**: Python bridge not running  
**Solution**: Run `تشغيل_برنامج_الارشفة.bat`

### "NAPS2 not installed" Error

**Problem**: NAPS2 not in default location  
**Solution**: Install NAPS2 to `C:\Program Files\NAPS2\`  
Or edit `NAPS2_PATH` in `scanner_bridge.py`

### "Scan timeout" Error

**Problem**: Scanner not responding  
**Solution**:
1. Check scanner USB connection
2. Test scan in NAPS2 application manually
3. Ensure paper is loaded in scanner
4. Restart scanner device

### Python Module Missing

**Problem**: `ModuleNotFoundError: No module named 'flask'`  
**Solution**: 
```bash
python -m pip install flask flask-cors
```

---

## 🔐 Security Notes

- Server runs on `localhost` only (not accessible from network)
- No authentication required (local machine only)
- CORS enabled for local web apps
- Files stored temporarily in system temp folder
- No data sent to external services

---

## 🎯 Usage Workflow

1. **Staff** opens surgery creation form
2. **Staff** places referral letter in scanner
3. **Staff** clicks "مسح الورقة من السكانر الآن" button
4. **Browser** sends POST to `localhost:37426/scan`
5. **Python** executes NAPS2 console command
6. **NAPS2** activates scanner hardware
7. **Scanner** captures document
8. **NAPS2** saves image to temp file
9. **Python** reads file and returns as HTTP response
10. **Browser** receives image blob
11. **JavaScript** displays preview and populates form field
12. **Form** submits with scanned image attached

---

## 📝 License

**100% Free and Open Source**

This system uses only free components:
- Python: [PSF License](https://docs.python.org/3/license.html)
- Flask: [BSD License](https://flask.palletsprojects.com/license/)
- NAPS2: [GPLv2 License](https://github.com/cyanfish/naps2)

---

## 👨‍💻 Technical Support

For issues:
1. Check Python is installed: `python --version`
2. Check NAPS2 is installed: Check `C:\Program Files\NAPS2\`
3. Test NAPS2 manually before using the web interface
4. Check scanner drivers are installed
5. Review logs in the Python console window

---

## 🌟 Credits

- **NAPS2**: Scanner interface by [cyanfish](https://github.com/cyanfish/naps2)
- **Flask**: Web framework by [Pallets Projects](https://palletsprojects.com/p/flask/)
- **Hospital System**: Custom development

---

**Last Updated**: 2024  
**System Version**: 1.0  
**Compatible**: Windows 10/11

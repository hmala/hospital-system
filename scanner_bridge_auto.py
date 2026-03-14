# ═══════════════════════════════════════════════════════════════════════════════
#   🖨️ نظام الأرشفة التلقائي - Hospital Auto Scanner Bridge
#   يعمل مع أي سكانر متصل بـ Windows تلقائياً (بدون NAPS2)
#   يستخدم Windows Image Acquisition (WIA) المدمج في Windows
# ═══════════════════════════════════════════════════════════════════════════════

import os
import sys
import time
import base64
import tempfile
import threading
from io import BytesIO

# محاولة استيراد المكتبات المطلوبة
try:
    from flask import Flask, request, send_file, jsonify
    from flask_cors import CORS
except ImportError:
    print("❌ خطأ: يرجى تثبيت Flask و Flask-CORS")
    print("   pip install flask flask-cors")
    sys.exit(1)

try:
    import win32com.client
    from PIL import Image
except ImportError:
    print("❌ خطأ: يرجى تثبيت المكتبات المطلوبة")
    print("   pip install pywin32 pillow")
    sys.exit(1)

# ═══════════════════════════════════════════════════════════════════════════════
#   إعدادات التطبيق
# ═══════════════════════════════════════════════════════════════════════════════

app = Flask(__name__)
CORS(app, resources={r"/*": {"origins": "*"}})

TEMP_DIR = tempfile.gettempdir()
PORT = 37426

# WIA Constants
WIA_DEVICE_TYPE_SCANNER = 1
WIA_IMG_FORMAT_JPEG = "{B96B3CAE-0728-11D3-9D7B-0000F81EF32E}"
WIA_IMG_FORMAT_PNG = "{B96B3CAF-0728-11D3-9D7B-0000F81EF32E}"
WIA_IMG_FORMAT_BMP = "{B96B3CAB-0728-11D3-9D7B-0000F81EF32E}"

# ═══════════════════════════════════════════════════════════════════════════════
#   وظائف السكانر
# ═══════════════════════════════════════════════════════════════════════════════

def get_available_scanners():
    """الحصول على قائمة السكانرات المتصلة"""
    scanners = []
    try:
        wia = win32com.client.Dispatch("WIA.DeviceManager")
        for i in range(1, wia.DeviceInfos.Count + 1):
            device_info = wia.DeviceInfos.Item(i)
            if device_info.Type == WIA_DEVICE_TYPE_SCANNER:
                scanners.append({
                    'id': device_info.DeviceID,
                    'name': device_info.Properties("Name").Value,
                    'manufacturer': device_info.Properties("Manufacturer").Value if hasattr(device_info.Properties("Manufacturer"), 'Value') else 'غير معروف'
                })
    except Exception as e:
        print(f"⚠️ خطأ في البحث عن السكانرات: {e}")
    return scanners

def scan_document(scanner_id=None, color_mode='color', resolution=200):
    """
    مسح مستند من السكانر
    
    Args:
        scanner_id: معرف السكانر (None = أول سكانر متاح)
        color_mode: 'color' / 'grayscale' / 'blackwhite'
        resolution: دقة المسح (150, 200, 300, 600)
    
    Returns:
        مسار الملف الممسوح أو None
    """
    try:
        wia = win32com.client.Dispatch("WIA.DeviceManager")
        
        # البحث عن السكانر
        device = None
        for i in range(1, wia.DeviceInfos.Count + 1):
            device_info = wia.DeviceInfos.Item(i)
            if device_info.Type == WIA_DEVICE_TYPE_SCANNER:
                if scanner_id is None or device_info.DeviceID == scanner_id:
                    device = device_info.Connect()
                    print(f"✓ تم الاتصال بالسكانر: {device_info.Properties('Name').Value}")
                    break
        
        if device is None:
            raise Exception("لم يتم العثور على سكانر متصل")
        
        # الحصول على العنصر القابل للمسح
        item = device.Items(1)
        
        # تعيين الإعدادات
        # Color Mode: 1=Color, 2=Grayscale, 4=Black&White
        color_modes = {'color': 1, 'grayscale': 2, 'blackwhite': 4}
        mode_value = color_modes.get(color_mode, 1)
        
        try:
            # محاولة تعيين إعدادات المسح (قد لا تدعمها جميع السكانرات)
            item.Properties("6146").Value = mode_value  # Color Intent
            item.Properties("6147").Value = resolution  # Horizontal Resolution
            item.Properties("6148").Value = resolution  # Vertical Resolution
        except Exception as e:
            print(f"⚠️ لم يتم تطبيق بعض الإعدادات: {e}")
        
        # تنفيذ المسح
        print("📄 جاري المسح...")
        image = item.Transfer(WIA_IMG_FORMAT_JPEG)
        
        # حفظ الصورة
        timestamp = int(time.time())
        output_file = os.path.join(TEMP_DIR, f'scan_{timestamp}.jpg')
        image.SaveFile(output_file)
        
        print(f"✓ تم حفظ الصورة: {output_file}")
        
        # تحسين الصورة (ضغطها إذا كانت كبيرة)
        optimize_image(output_file)
        
        return output_file
        
    except Exception as e:
        print(f"❌ خطأ في المسح: {e}")
        raise e

def optimize_image(filepath, max_size_kb=500, quality=85):
    """تحسين وضغط الصورة"""
    try:
        img = Image.open(filepath)
        
        # تصغير الصورة إذا كانت كبيرة جداً
        max_dimension = 2000
        if img.width > max_dimension or img.height > max_dimension:
            ratio = min(max_dimension / img.width, max_dimension / img.height)
            new_size = (int(img.width * ratio), int(img.height * ratio))
            img = img.resize(new_size, Image.Resampling.LANCZOS)
        
        # حفظ بجودة محسنة
        img.save(filepath, 'JPEG', quality=quality, optimize=True)
        
        # إذا كان الملف لا يزال كبيراً، نقلل الجودة
        file_size = os.path.getsize(filepath) / 1024  # KB
        while file_size > max_size_kb and quality > 50:
            quality -= 10
            img.save(filepath, 'JPEG', quality=quality, optimize=True)
            file_size = os.path.getsize(filepath) / 1024
        
        print(f"✓ حجم الصورة النهائي: {file_size:.0f} KB")
        
    except Exception as e:
        print(f"⚠️ لم يتم تحسين الصورة: {e}")

# ═══════════════════════════════════════════════════════════════════════════════
#   API Endpoints
# ═══════════════════════════════════════════════════════════════════════════════

@app.route('/scan', methods=['POST', 'OPTIONS'])
def api_scan():
    """نقطة نهاية المسح الرئيسية"""
    
    if request.method == 'OPTIONS':
        return '', 200
    
    try:
        # قراءة الإعدادات من الطلب
        data = request.get_json() or {}
        scanner_id = data.get('scanner_id', None)
        color_mode = data.get('color_mode', 'color')
        resolution = data.get('resolution', 200)
        return_base64 = data.get('base64', False)
        
        # تنفيذ المسح
        output_file = scan_document(scanner_id, color_mode, resolution)
        
        if output_file and os.path.exists(output_file):
            if return_base64:
                # إرجاع base64
                with open(output_file, 'rb') as f:
                    image_data = base64.b64encode(f.read()).decode('utf-8')
                return jsonify({
                    'success': True,
                    'image': f'data:image/jpeg;base64,{image_data}'
                })
            else:
                # إرجاع الملف مباشرة
                return send_file(
                    output_file,
                    mimetype='image/jpeg',
                    as_attachment=False
                )
        else:
            return jsonify({
                'success': False,
                'error': 'فشل المسح - لم يتم إنشاء الصورة'
            }), 500
            
    except Exception as e:
        error_msg = str(e)
        status_code = 500
        
        if 'لم يتم العثور على سكانر' in error_msg:
            status_code = 404
            error_msg = 'لم يتم العثور على سكانر متصل. تأكد من توصيل السكانر وتشغيله.'
        
        return jsonify({
            'success': False,
            'error': error_msg
        }), status_code

@app.route('/scanners', methods=['GET'])
def api_list_scanners():
    """الحصول على قائمة السكانرات المتصلة"""
    try:
        scanners = get_available_scanners()
        return jsonify({
            'success': True,
            'scanners': scanners,
            'count': len(scanners)
        })
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/status', methods=['GET'])
def api_status():
    """التحقق من حالة النظام"""
    try:
        scanners = get_available_scanners()
        return jsonify({
            'status': 'ready',
            'scanners_count': len(scanners),
            'scanners': scanners,
            'message': f'جاهز - {len(scanners)} سكانر متصل' if scanners else 'لا يوجد سكانر متصل'
        })
    except Exception as e:
        return jsonify({
            'status': 'error',
            'error': str(e)
        }), 500

@app.route('/', methods=['GET'])
def api_home():
    """الصفحة الرئيسية"""
    return jsonify({
        'name': 'Hospital Auto Scanner Bridge',
        'version': '2.0',
        'status': 'running',
        'endpoints': {
            '/scan': 'POST - مسح مستند',
            '/scanners': 'GET - قائمة السكانرات',
            '/status': 'GET - حالة النظام'
        }
    })

# ═══════════════════════════════════════════════════════════════════════════════
#   تشغيل الخادم
# ═══════════════════════════════════════════════════════════════════════════════

def print_banner():
    """طباعة شعار البرنامج"""
    print()
    print("╔═══════════════════════════════════════════════════════════════════╗")
    print("║  🖨️  نظام الأرشفة التلقائي - Hospital Auto Scanner Bridge v2.0  ║")
    print("║     يعمل مع أي سكانر متصل بـ Windows تلقائياً                    ║")
    print("╠═══════════════════════════════════════════════════════════════════╣")
    print(f"║  🌐 الخادم يعمل على: http://localhost:{PORT}                      ║")
    print("║  📡 جاهز لاستقبال طلبات المسح من الموقع                          ║")
    print("╚═══════════════════════════════════════════════════════════════════╝")
    print()

def check_scanners_on_start():
    """فحص السكانرات عند بدء التشغيل"""
    print("🔍 جاري البحث عن السكانرات المتصلة...")
    scanners = get_available_scanners()
    
    if scanners:
        print(f"✓ تم العثور على {len(scanners)} سكانر:")
        for i, scanner in enumerate(scanners, 1):
            print(f"   {i}. {scanner['name']} ({scanner['manufacturer']})")
    else:
        print("⚠️ لم يتم العثور على سكانرات متصلة")
        print("   تأكد من توصيل السكانر وتثبيت التعريفات")
    print()

if __name__ == '__main__':
    print_banner()
    check_scanners_on_start()
    
    print("💡 للإيقاف: اضغط Ctrl+C")
    print("═" * 60)
    print()
    
    # تشغيل الخادم
    app.run(host='127.0.0.1', port=PORT, debug=False, threaded=True)

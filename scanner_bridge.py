# ═══════════════════════════════════════════════════════════
#   برنامج الأرشفة المجاني - Hospital Scanner Bridge
#   يربط الموقع بالسكانر مباشرة (مجاني 100%)
# ═══════════════════════════════════════════════════════════

import subprocess
import os
import time
from flask import Flask, request, send_file, jsonify
from flask_cors import CORS
import tempfile

app = Flask(__name__)
CORS(app)  # السماح بالاتصال من الموقع

@app.after_request
def add_cors_headers(response):
    response.headers['Access-Control-Allow-Origin'] = '*'
    response.headers['Access-Control-Allow-Methods'] = 'GET, POST, OPTIONS'
    response.headers['Access-Control-Allow-Headers'] = 'Content-Type, Authorization, X-Requested-With'
    response.headers['Access-Control-Allow-Private-Network'] = 'true'
    return response

# مسار NAPS2 (سيتم تثبيته مجاناً)
NAPS2_PATH = r"C:\Program Files\NAPS2\naps2.console.exe"
TEMP_DIR = tempfile.gettempdir()

@app.route('/scan', methods=['POST', 'OPTIONS'])
def scan_document():
    """المسح من السكانر وإرجاع الصورة"""
    
    if request.method == 'OPTIONS':
        resp = app.make_response(('', 204))
        resp.headers['Access-Control-Allow-Origin'] = '*'
        resp.headers['Access-Control-Allow-Methods'] = 'POST, OPTIONS'
        resp.headers['Access-Control-Allow-Headers'] = 'Content-Type, Authorization, X-Requested-With'
        resp.headers['Access-Control-Allow-Private-Network'] = 'true'
        return resp
    
    try:
        # إنشاء اسم ملف مؤقت
        timestamp = int(time.time())
        output_file = os.path.join(TEMP_DIR, f'scanned_{timestamp}.jpg')
        
        # تشغيل NAPS2 للمسح
        # الأمر: naps2.console.exe -o "output.jpg" -n 1
        command = [
            NAPS2_PATH,
            '-o', output_file,
            '-n', '1',  # مسح صفحة واحدة
            '--jpegquality', '90',
            '--enableocr', 'false'
        ]
        
        print(f'جاري المسح باستخدام: {" ".join(command)}')
        
        # تنفيذ الأمر
        result = subprocess.run(
            command,
            capture_output=True,
            text=True,
            timeout=60  # timeout بعد دقيقة
        )
        
        if result.returncode != 0:
            return jsonify({
                'error': 'فشل المسح',
                'details': result.stderr
            }), 500
        
        # التحقق من وجود الملف
        if not os.path.exists(output_file):
            return jsonify({
                'error': 'لم يتم إنشاء الصورة'
            }), 500
        
        # إرجاع الصورة
        return send_file(
            output_file,
            mimetype='image/jpeg',
            as_attachment=False
        )
        
    except FileNotFoundError:
        return jsonify({
            'error': 'NAPS2 غير مثبت',
            'message': 'يرجى تثبيت NAPS2 من: https://github.com/cyanfish/naps2/releases'
        }), 404
        
    except subprocess.TimeoutExpired:
        return jsonify({
            'error': 'انتهت مهلة المسح',
            'message': 'تأكد من توصيل السكانر'
        }), 408
        
    except Exception as e:
        return jsonify({
            'error': str(e)
        }), 500

@app.route('/status', methods=['GET'])
def check_status():
    """التحقق من جاهزية السكانر"""
    
    # التحقق من وجود NAPS2
    if not os.path.exists(NAPS2_PATH):
        return jsonify({
            'status': 'error',
            'message': 'NAPS2 غير مثبت'
        }), 404
    
    return jsonify({
        'status': 'ready',
        'message': 'جاهز للمسح'
    })

if __name__ == '__main__':
    print('═══════════════════════════════════════════════════')
    print('  🖨️  برنامج الأرشفة - Hospital Scanner Bridge')
    print('═══════════════════════════════════════════════════')
    print()
    port = int(os.environ.get('SCANNER_PORT', 5000))
    print(f'✓ الخادم يعمل على: http://localhost:{port}')
    print('✓ جاهز لاستقبال طلبات المسح من الموقع')
    print()
    print('💡 لإيقاف البرنامج: اضغط Ctrl+C')
    print('═══════════════════════════════════════════════════')
    print()
    
    # تشغيل الخادم
    try:
        app.run(host='127.0.0.1', port=port, debug=False)
    except OSError:
        if port != 37426:
            print(f"⚠️ المنفذ {port} مشغول، جاري التبديل للمنفذ الاحتياطي 37426...")
            app.run(host='127.0.0.1', port=37426, debug=False)

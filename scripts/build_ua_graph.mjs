import fs from 'fs';
import path from 'path';

const projectRoot = process.cwd();
const uaDir = path.join(projectRoot, '.ua');
if (!fs.existsSync(uaDir)) {
  fs.mkdirSync(uaDir, { recursive: true });
}

console.log('Generating ultra-rich knowledge-graph.json for hospital-system...');

// Read Git commit
let gitCommit = '94475595a3de134977abf1a6b63c317495110e30';
try {
  gitCommit = fs.readFileSync(path.join(projectRoot, '.git/HEAD'), 'utf-8').trim();
  if (gitCommit.startsWith('ref: ')) {
    const refPath = path.join(projectRoot, '.git', gitCommit.substring(5).trim());
    if (fs.existsSync(refPath)) {
      gitCommit = fs.readFileSync(refPath, 'utf-8').trim();
    }
  }
} catch (e) {}

const nodes = [];
const edges = [];
const nodeMap = new Set();
const edgeSet = new Set();

function addNode(node) {
  if (!nodeMap.has(node.id)) {
    nodeMap.add(node.id);
    nodes.push(node);
  }
}

function addEdge(source, target, type, description, weight = 0.8) {
  const edgeKey = `${source}->${target}:${type}`;
  if (nodeMap.has(source) && nodeMap.has(target) && !edgeSet.has(edgeKey)) {
    edgeSet.add(edgeKey);
    edges.push({
      source,
      target,
      type,
      direction: 'forward',
      description,
      weight
    });
  }
}

// 1. Scan app/Models
const modelsDir = path.join(projectRoot, 'app/Models');
const modelContentMap = new Map();

if (fs.existsSync(modelsDir)) {
  const modelFiles = fs.readdirSync(modelsDir).filter(f => f.endsWith('.php'));
  for (const file of modelFiles) {
    const name = file.replace('.php', '');
    const id = `model:${name}`;
    const filePath = `app/Models/${file}`;
    const content = fs.readFileSync(path.join(modelsDir, file), 'utf-8');
    modelContentMap.set(name, content);
    const lines = content.split('\n').length;
    
    const tags = ['model', 'eloquent', 'database'];
    let complexity = 'simple';
    if (lines > 300) complexity = 'complex';
    else if (lines > 120) complexity = 'moderate';

    let summary = `Eloquent Model representing ${name} entity in hospital database.`;
    if (name.includes('Payment')) summary = 'إدارة الحركات المالية وسندات القبض والدفعات النقدية والضمان.';
    else if (name.includes('Patient')) summary = 'ملف المريض الطبي الدائم والبيانات الديموغرافية والتأمينية.';
    else if (name.includes('Appointment')) summary = 'إدارة حجز المواعيد وأدوار وطوابير الانتظار في العيادات.';
    else if (name.includes('DoctorVisit')) summary = 'معاينة المريض في الاستشارية والتشخيص وخطة العلاج.';
    else if (name.includes('DoctorRequest')) summary = 'طلبات الفحوصات والتحاليل والوصفات الطبية الصادرة من الطبيب.';
    else if (name.includes('Surgery')) summary = 'سجل العمليات الجراحية وجدولة الصالات وتتبع المراحل السريرية.';
    else if (name.includes('BedReservation')) summary = 'إدارة حجز أسرة الرقود والغرف والأجنحة الفندقية بالمستشفى.';
    else if (name.includes('IncubatorReservation')) summary = 'سجل رقود الخدج في الحاضنات واحتساب الساعات ومصفوفة عمر الحمل.';
    else if (name.includes('InpatientRoomSurvey')) summary = 'منظومة استبيان وتقييم خدمات الغرف وجودة العناية الطبية عبر الباركود.';
    else if (name.includes('LabRequest')) summary = 'طلبات المختبر والتحاليل الطبية والنتائج والاعتماد المخبري.';
    else if (name.includes('Location')) summary = 'مواقع ومستودعات المستشفى المركزية ومخازن الطوابق والعمليات.';
    else if (name.includes('InventoryBatch')) summary = 'الوجبات المخزنية للمواد والأدوية وأرصدتها وتواريخ الصلاحية.';
    else if (name.includes('User')) summary = 'سجلات مستخدمي النظام وكادر المستشفى الطبي والإداري.';

    addNode({
      id,
      type: 'table',
      name,
      filePath,
      lineRange: [1, lines],
      summary,
      tags,
      complexity
    });
  }
}

// 2. Scan app/Http/Controllers
const controllerContentMap = new Map();

function scanControllers(dir, prefix = '') {
  if (!fs.existsSync(dir)) return;
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  for (const entry of entries) {
    if (entry.isDirectory()) {
      scanControllers(path.join(dir, entry.name), `${prefix}${entry.name}/`);
    } else if (entry.name.endsWith('Controller.php')) {
      const name = entry.name.replace('.php', '');
      const relPath = `app/Http/Controllers/${prefix}${entry.name}`;
      const id = `controller:${prefix}${name}`;
      const content = fs.readFileSync(path.join(dir, entry.name), 'utf-8');
      controllerContentMap.set(id, { name, prefix, content });
      const lines = content.split('\n').length;
      
      const tags = ['controller', 'http', 'api'];
      let complexity = lines > 600 ? 'complex' : lines > 200 ? 'moderate' : 'simple';

      let summary = `HTTP Controller handling web and API routes for ${name}.`;
      if (name === 'CashierController') summary = 'معالجة مدفوعات الكاشير، كشوفات المرضى، تصفيات الخدج، والوصولات الحرارية.';
      else if (name === 'HospitalTreasuryController') summary = 'الخزينة المركزية، رصد السيولة اليومية، حصص الأطباء، والذكاء المالي BI.';
      else if (name === 'DoctorVisitController') summary = 'محطة الطبيب السريرية، الكشف الطبي، الوصفات، وطلبات الفحوصات.';
      else if (name === 'AppointmentController') summary = 'نظام حجز المواعيد وتنظيم طوابير الاستشارية وإلغاء الكشفيات.';
      else if (name === 'LabStaffController') summary = 'محطة المختبر، إدخال النتائج الطبية، وتدقيق وطباعة التحاليل.';
      else if (name === 'RadiologyStaffController') summary = 'محطة الأشعة والسونار، الاستدعاء الصوتي للمرضى، والتقارير الإشعاعية.';
      else if (name === 'SurgeryController') summary = 'إدارة صالات العمليات الجراحية، قوائم الانتظار، والاستهلاك السريري.';
      else if (name === 'InpatientSurveyController') summary = 'إدارة استبيانات الرقود، مسح الـ QR بالموبايل، وطباعة بطاقات التقييم.';
      else if (name === 'IncubatorReservationController') summary = 'إدارة الحاضنات والخدج، احتساب الفواتير الفندقية، وتوزيع مبالغ الضمان.';

      addNode({
        id,
        type: 'endpoint',
        name,
        filePath: relPath,
        lineRange: [1, lines],
        summary,
        tags,
        complexity
      });
    }
  }
}
scanControllers(path.join(projectRoot, 'app/Http/Controllers'));

// 3. Scan app/Services
const servicesDir = path.join(projectRoot, 'app/Services');
if (fs.existsSync(servicesDir)) {
  const serviceFiles = fs.readdirSync(servicesDir).filter(f => f.endsWith('.php'));
  for (const file of serviceFiles) {
    const name = file.replace('.php', '');
    const id = `service:${name}`;
    const filePath = `app/Services/${file}`;
    const content = fs.readFileSync(path.join(servicesDir, file), 'utf-8');
    const lines = content.split('\n').length;

    let summary = `Business Domain Service executing core business logic for ${name}.`;
    if (name === 'NicuBillingService') summary = 'محرك الفوترة الحية للخدج، عداد الأيام التراكمية، ومصفوفة عمر الحمل الوزارية.';
    else if (name === 'DatabaseSyncService') summary = 'محرك المزامنة السحابية اللحظية مع هوستنجر وعمل النسخ الاحتياطية الآلية.';
    else if (name === 'UserActivityLogger') summary = 'سجل الحركات والنشاط التشغيلي لكادر المستشفى ومراقبة العمليات الحية.';

    addNode({
      id,
      type: 'service',
      name,
      filePath,
      lineRange: [1, lines],
      summary,
      tags: ['service', 'business-logic'],
      complexity: lines > 300 ? 'complex' : 'moderate'
    });
  }
}

// 4. Scan Core Routes
const routes = [
  { id: 'route:web', name: 'Web Routes', filePath: 'routes/web.php', summary: 'مسارات واجهات المستخدم، الاستشارية، الكاشير، والخزينة' },
  { id: 'route:api', name: 'API Routes', filePath: 'routes/api.php', summary: 'نقاط نهاية الـ REST API والمزامنة وقنوات البث' },
  { id: 'route:channels', name: 'Broadcast Channels', filePath: 'routes/channels.php', summary: 'قنوات الإشعار الحي عبر Laravel Echo و Pusher' }
];
for (const r of routes) {
  if (fs.existsSync(path.join(projectRoot, r.filePath))) {
    const content = fs.readFileSync(path.join(projectRoot, r.filePath), 'utf-8');
    addNode({
      id: r.id,
      type: 'module',
      name: r.name,
      filePath: r.filePath,
      lineRange: [1, content.split('\n').length],
      summary: r.summary,
      tags: ['routes', 'routing'],
      complexity: 'moderate'
    });
  }
}

// 5. Connect Automatic Model Relations
for (const [modelName, content] of modelContentMap.entries()) {
  const modelId = `model:${modelName}`;
  for (const [targetName] of modelContentMap.entries()) {
    if (targetName === modelName) continue;
    const targetId = `model:${targetName}`;
    // Look for relation patterns like belongsTo(Target::class) or hasMany(Target::class)
    const relRegex = new RegExp(`(hasMany|belongsTo|hasOne|belongsToMany|morphTo|morphMany)\\s*\\(\\s*${targetName}::class`, 'i');
    if (relRegex.test(content)) {
      addEdge(modelId, targetId, 'depends_on', `علاقة ربط Eloquent (${modelName} ↔ ${targetName})`);
    }
  }
}

// 6. Connect Controller -> Model & Service Dependencies
for (const [ctrlId, ctrlData] of controllerContentMap.entries()) {
  const content = ctrlData.content;
  // Models used
  for (const [modelName] of modelContentMap.entries()) {
    if (content.includes(`App\\Models\\${modelName}`) || content.includes(`${modelName}::`) || content.includes(`${modelName} $`)) {
      addEdge(ctrlId, `model:${modelName}`, 'calls', `إدارة واستعلام بيانات ${modelName}`);
    }
  }
  // Services used
  const serviceFiles = fs.existsSync(servicesDir) ? fs.readdirSync(servicesDir).filter(f => f.endsWith('.php')) : [];
  for (const sFile of serviceFiles) {
    const sName = sFile.replace('.php', '');
    if (content.includes(sName)) {
      addEdge(ctrlId, `service:${sName}`, 'calls', `استدعاء خدمة ${sName}`);
    }
  }
}

// 7. Connect Routes -> Controllers
const webRouteContent = fs.existsSync(path.join(projectRoot, 'routes/web.php')) ? fs.readFileSync(path.join(projectRoot, 'routes/web.php'), 'utf-8') : '';
for (const [ctrlId, ctrlData] of controllerContentMap.entries()) {
  if (webRouteContent.includes(ctrlData.name)) {
    addEdge('route:web', ctrlId, 'routes', `توجيه مسارات الويب إلى ${ctrlData.name}`);
  }
}

// 8. Define Architectural Layers
const layers = [
  {
    id: 'layer:clinical_outpatient',
    name: '🏥 Outpatient & Clinic Stations (العيادات والاستشارية ومحطة الطبيب)',
    description: 'إدارة دورة حياة المريض من حجز الموعد، استدعاء الطابور، الكشف السريري، وحتى الوصفة الطبية.',
    nodeIds: [
      'controller:DoctorVisitController',
      'controller:AppointmentController',
      'model:DoctorVisit',
      'model:Appointment',
      'model:DoctorRequest',
      'model:Patient'
    ]
  },
  {
    id: 'layer:cashier_treasury',
    name: '💰 Cashier, Payments & Treasury (الكاشير، المدفوعات والخزينة المركزية)',
    description: 'المنظومة المالية المتكاملة، تسديد الدفعات، مطالبات الضمان، وذكاء الأعمال للخزينة.',
    nodeIds: [
      'controller:CashierController',
      'controller:HospitalTreasuryController',
      'model:Payment',
      'service:NicuBillingService'
    ]
  },
  {
    id: 'layer:diagnostics',
    name: '🔬 Diagnostic Stations (المختبر والأشعة والسونار)',
    description: 'محطات الفحص التشخيصي، الاستدعاء الصوتي، تسجيل النتائج، وطباعة التقارير المعتمدة.',
    nodeIds: [
      'controller:LabStaffController',
      'controller:RadiologyStaffController',
      'model:LabRequest'
    ]
  },
  {
    id: 'layer:inpatient_surgery',
    name: '🏨 Inpatient & Surgical Department (الرقود والعمليات والمحطة السريرية)',
    description: 'صالات العمليات، إدارة الأسرة والغرف، استهلاك المواد التمريضي، واستبيان رضا المريض.',
    nodeIds: [
      'controller:SurgeryController',
      'controller:BedReservationController',
      'controller:InpatientSurveyController',
      'model:Surgery',
      'model:BedReservation',
      'model:InpatientRoomSurvey'
    ]
  },
  {
    id: 'layer:nicu',
    name: '👶 NICU & Incubator Care (الحاضنات والأطفال الخدج والتأمين)',
    description: 'رعاية الخدج، التوقيت الفندقي، احتساب الساعات التراكمية، ومطالبات هيئة الضمان الصحي.',
    nodeIds: [
      'controller:IncubatorReservationController',
      'model:IncubatorReservation',
      'service:NicuBillingService'
    ]
  },
  {
    id: 'layer:inventory',
    name: '📦 Warehouses & Pharmacy (المخازن المركزية ومخازن الأجنحة)',
    description: 'المستودع الرئيسي، مخازن الطوابق، التحويلات المخزنية، ووجبات الأدوية والمستلزمات.',
    nodeIds: [
      'model:Location',
      'model:InventoryBatch',
      'model:StockMovement',
      'model:StockRequest'
    ]
  },
  {
    id: 'layer:security_sync',
    name: '🛡️ Core Infrastructure, Activity & Sync (البنية التحتية والنبض اللحظي)',
    description: 'تسجيل أنشطة المستخدمين، المزامنة السحابية مع هوستنجر، ومنظومة المسارات.',
    nodeIds: [
      'service:DatabaseSyncService',
      'service:UserActivityLogger',
      'route:web',
      'route:api',
      'route:channels'
    ]
  }
];

// 9. Guided Tour Steps
const tour = [
  {
    order: 1,
    title: '1. دورة حياة المريض في الاستشارية (Outpatient Lifecycle)',
    description: 'تبدأ رحلة المريض بحجز الموعد لدى كاونتر الاستقبال (Appointment)، ثم تسديد رسوم الكشفية لدى الكاشير (Payment)، ليدخل تلقائياً في طابور انتظار عيادة الطبيب (DoctorVisit) لإجراء الفحص وكتابة التشخيص والوصفة الطبية.',
    nodeIds: ['model:Patient', 'model:Appointment', 'controller:AppointmentController', 'controller:DoctorVisitController', 'model:DoctorVisit']
  },
  {
    order: 2,
    title: '2. الكاشير والتحصيل المالي والضمان (Cashier & Insurance Settlement)',
    description: 'يتعامل الكاشير مع سداد كشفية الطبيب، ودفعات التحاليل والأشعة، وتصفيات الخدج. يتم تقسيم الدفعة تلقائياً بين حصة المريض النقدية وحصة الضمان الصحي (Health Insurance) كمطالبة ذمة مدينة على هيئة الضمان.',
    nodeIds: ['controller:CashierController', 'model:Payment', 'service:NicuBillingService', 'controller:HospitalTreasuryController']
  },
  {
    order: 3,
    title: '3. صالات العمليات والمحطة السريرية واستبيان الرقود (Surgery & Inpatient EHR)',
    description: 'إدارة متكاملة لصالات العمليات الجراحية وقوائم الانتظار والمحطة السريرية الموحدة (Pre-Op/Post-Op)، مع تتبع استهلاك المستلزمات الطبية من مخزن العمليات الفرعي، ونظام استبيان تقييم الغرفة بالـ QR الحراري.',
    nodeIds: ['controller:SurgeryController', 'model:Surgery', 'controller:BedReservationController', 'controller:InpatientSurveyController', 'model:InpatientRoomSurvey']
  },
  {
    order: 4,
    title: '4. رعاية الخدج ومصفوفة عمر الحمل الوزارية (NICU Incubator & Gestational Matrix)',
    description: 'محرك الفوترة الحية التلقائي لحساب ساعات وأيام مكوث الطفل في الحاضنة، واحتساب الفاتورة بدقة وتطبيق مصفوفة عمر الحمل الوزارية الأسبوعية وتحديد الفرق الصافي الذي تطالب به المستشفى هيئة الضمان.',
    nodeIds: ['controller:IncubatorReservationController', 'model:IncubatorReservation', 'service:NicuBillingService', 'controller:HospitalTreasuryController']
  },
  {
    order: 5,
    title: '5. الخزينة المركزية وذكاء الأعمال والنبض اللحظي (Treasury & Live Radar)',
    description: 'اللوحة القيادية المركزية لمدير المستشفى لمتابعة الإيرادات اللحظية، السيولة النقدية، حصص الأطباء، مصفوفة الإحالات الطبية، وسجل النشاط اللحظي لكادر المستشفى والمزامنة السحابية.',
    nodeIds: ['controller:HospitalTreasuryController', 'service:UserActivityLogger', 'service:DatabaseSyncService', 'route:web']
  }
];

const knowledgeGraph = {
  version: '1.0',
  kind: 'codebase',
  project: {
    name: 'hospital-system',
    languages: ['PHP 8.2', 'JavaScript (ESM)', 'Blade Templates', 'Tailwind CSS', 'Bootstrap 5'],
    frameworks: ['Laravel 12', 'Vite', 'Chart.js v4', 'Spatie Permission', 'Laravel Echo'],
    description: 'Comprehensive Hospital Management System (نظام إدارة المستشفى المتكامل: العيادات، الكاشير، الخزينة، الرقود، العمليات، الحاضنات، والمختبر)',
    analyzedAt: new Date().toISOString(),
    gitCommitHash: gitCommit
  },
  nodes,
  edges,
  layers,
  tour
};

fs.writeFileSync(path.join(uaDir, 'knowledge-graph.json'), JSON.stringify(knowledgeGraph, null, 2));

const meta = {
  lastAnalyzedAt: new Date().toISOString(),
  gitCommitHash: gitCommit,
  version: '1.0',
  analyzedFiles: nodes.length
};
fs.writeFileSync(path.join(uaDir, 'meta.json'), JSON.stringify(meta, null, 2));

console.log(`Knowledge graph created successfully! Total Nodes: ${nodes.length}, Total Edges: ${edges.length}, Layers: ${layers.length}`);

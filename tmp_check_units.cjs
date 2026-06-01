const fs = require('fs');
const data = fs.readFileSync('database/seeders/LabTestsSeeder.php','utf8').split(/\r?\n/);
let five=0, six=0;
const fiveLines=[];
for(const line of data){
  const m5=line.match(/^\s*\['[^']+',\s*'[^']+',\s*'[^']+',\s*'[^']+',\s*'[^']+'\],\s*$/);
  const m6=line.match(/^\s*\['[^']+',\s*'[^']+',\s*'[^']+',\s*'[^']+',\s*'[^']+',\s*'[^']+'\],\s*$/);
  if(m5) { five++; if(five<=10) fiveLines.push(line); }
  else if(m6) six++;
}
console.log(JSON.stringify({five,six,firstFive:fiveLines}, null, 2));

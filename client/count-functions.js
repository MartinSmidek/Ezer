const fs = require("fs");
const path = require("path");

// Vlastní require k pevně danému umístění parseru
const parser = require("./licensed/node_modules/@babel/parser");

// Čtení pouze .js souborů v dané složce (bez rekurze)
function readJSFiles(dir) {
  return fs.readdirSync(dir)
    .map(file => path.resolve(dir, file))
    .filter(file => fs.statSync(file).isFile() && file.endsWith(".js"));
}

// Spočítání funkcí v AST

var count_f=0, count_m= 0;

function countFunctionsInCode(code) {
  const ast = parser.parse(code, {
    sourceType: "unambiguous",
    plugins: ["jsx", "classProperties"]
  });

  count_f= count_m= 0;

  function traverse(node) {
    if (!node || typeof node !== "object") return;

    switch (node.type) {
      case "FunctionDeclaration":
      case "FunctionExpression":
      case "ArrowFunctionExpression":
        count_f++;
        break;
      case "ObjectMethod":
      case "ClassMethod":
        count_m++;
        break;
    }

    for (let key in node) {
      const child = node[key];
      if (Array.isArray(child)) {
        child.forEach(traverse);
      } else {
        traverse(child);
      }
    }
  }

  traverse(ast.program);
//  return count;
}

// === Hlavní běh ===

const targetDir = process.argv[2] || ".";
const files = readJSFiles(targetDir);

let total_f= 0, total_m= 0, ret= '';

for (let file of files) {
  const code = fs.readFileSync(file, "utf-8");
//  const count = countFunctionsInCode(code);
  countFunctionsInCode(code);
  ret+= `,${path.basename(file)}:${count_f}:${count_m}`;
  total_f+= count_f;
  total_m+= count_m;
}
ret= `total:${total_f}:${total_m}${ret}`;

console.log(ret);

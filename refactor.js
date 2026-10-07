const { Project, SyntaxKind } = require('ts-morph');
const fs = require('fs');

const project = new Project({
    tsConfigFilePath: './tsconfig.json',
});

project.addSourceFilesAtPaths('resources/js/**/*.tsx');

const translationKeys = new Set();
let count = 0;

for (const sourceFile of project.getSourceFiles()) {
    if (sourceFile.getFilePath().endsWith('profile.tsx')) continue;

    let modified = false;

    // We only care about React components, let's just look for JSX Elements
    const jsxTexts = sourceFile.getDescendantsOfKind(SyntaxKind.JsxText);
    for (const jsxText of jsxTexts) {
        const text = jsxText.getLiteralText();
        const trimmed = text.trim();
        // Check if it's actual text
        if (trimmed.length > 0 && /[A-Za-z]/.test(trimmed)) {
            // Replace with {t('Text')}
            jsxText.replaceWithText(`{t('${trimmed.replace(/'/g, "\\'")}')}`);
            translationKeys.add(trimmed);
            modified = true;
            count++;
        }
    }

    // Now let's look for common JsxAttributes like placeholder, title, label
    const jsxAttributes = sourceFile.getDescendantsOfKind(SyntaxKind.JsxAttribute);
    for (const attr of jsxAttributes) {
        const name = attr.getName();
        if (['placeholder', 'title', 'label', 'description', 'alt', 'confirmText', 'cancelText', 'heading'].includes(name)) {
            const init = attr.getInitializer();
            if (init && init.getKind() === SyntaxKind.StringLiteral) {
                const text = init.getLiteralText();
                if (text.trim().length > 0 && /[A-Za-z]/.test(text)) {
                    attr.setInitializer(`{t('${text.replace(/'/g, "\\'")}')}`);
                    translationKeys.add(text);
                    modified = true;
                    count++;
                }
            }
        }
    }

    if (modified) {
        // Ensure useTranslate is imported
        const hasUseTranslate = sourceFile.getImportDeclarations().some(i => 
            i.getModuleSpecifierValue() === '@/hooks/useTranslate'
        );

        if (!hasUseTranslate) {
            sourceFile.addImportDeclaration({
                namedImports: ['useTranslate'],
                moduleSpecifier: '@/hooks/useTranslate'
            });
        }

        // Add `const { t } = useTranslate();` to the component body if not present
        // This is tricky because there could be multiple components. Let's just find functions that return JSX.
        const functions = sourceFile.getFunctions();
        const arrowFunctions = sourceFile.getDescendantsOfKind(SyntaxKind.ArrowFunction);
        const allFunctions = [...functions, ...arrowFunctions];

        for (const func of allFunctions) {
            const hasJsx = func.getDescendantsOfKind(SyntaxKind.JsxElement).length > 0 || 
                           func.getDescendantsOfKind(SyntaxKind.JsxSelfClosingElement).length > 0 ||
                           func.getDescendantsOfKind(SyntaxKind.JsxFragment).length > 0;
            
            if (hasJsx) {
                const body = func.getBody();
                if (body && body.getKind() === SyntaxKind.Block) {
                    const statements = body.getStatements();
                    const hasT = statements.some(s => s.getText().includes('useTranslate('));
                    if (!hasT) {
                        body.insertStatements(0, 'const { t } = useTranslate();');
                    }
                }
            }
        }

        sourceFile.saveSync();
        console.log(`Updated ${sourceFile.getFilePath()}`);
    }
}

console.log(`Replaced ${count} texts.`);
console.log('Keys:', Array.from(translationKeys).join('|||'));
fs.writeFileSync('new_keys.json', JSON.stringify(Array.from(translationKeys), null, 2));


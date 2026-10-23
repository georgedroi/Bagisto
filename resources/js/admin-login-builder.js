import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';

document.addEventListener('DOMContentLoaded', () => {
    const mount = document.getElementById('manpleasure-login-builder');

    if (!mount) {
        return;
    }

    const readJson = (id, fallback = null) => {
        try {
            return JSON.parse(document.getElementById(id)?.textContent ?? 'null') ?? fallback;
        } catch {
            return fallback;
        }
    };

    const projectData = readJson('builder-project-data');
    const savedHtml = readJson('builder-html-data', '');
    const savedCss = readJson('builder-css-data', '');

    const starterHtml = `
        <section class="msp-login-hero">
            <div class="msp-login-content">
                <p class="msp-eyebrow">MAN SECRET PLEASURE</p>
                <h1>Make your first impression count.</h1>
                <p>Use this space for your brand message, background, imagery and layout.</p>
                <div class="msp-image-placeholder">Add your brand image or logo here</div>
            </div>
        </section>
    `;

    const starterCss = `
        .msp-login-hero {
            box-sizing: border-box;
            min-height: 480px;
            padding: 64px 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #111827;
            color: #ffffff;
            font-family: Arial, sans-serif;
        }
        .msp-login-content {
            width: 100%;
            max-width: 760px;
            margin: 0 auto;
            text-align: center;
        }
        .msp-eyebrow {
            letter-spacing: 0.2em;
            font-size: 12px;
            font-weight: 700;
        }
        .msp-login-content h1 {
            margin: 20px 0;
            font-size: clamp(30px, 5vw, 52px);
            line-height: 1.1;
        }
        .msp-login-content p {
            line-height: 1.7;
        }
        .msp-image-placeholder {
            margin: 32px auto 0;
            max-width: 360px;
            padding: 32px;
            border: 1px dashed #9ca3af;
            color: #d1d5db;
        }
    `;

    const editor = grapesjs.init({
        container: mount,
        height: '75vh',
        fromElement: false,
        storageManager: false,
        selectorManager: { componentFirst: true },
        panels: { defaults: [] },
        blockManager: {
            appendTo: '#manpleasure-login-builder',
            blocks: [
                {
                    id: 'section',
                    label: 'Section',
                    category: 'Layout',
                    content: '<section style="padding:40px 20px"><h2>Section heading</h2><p>Add your content here.</p></section>',
                },
                {
                    id: 'container',
                    label: 'Container',
                    category: 'Layout',
                    content: '<div style="max-width:1100px;margin:0 auto;padding:20px">Container</div>',
                },
                {
                    id: 'two-columns',
                    label: 'Two columns',
                    category: 'Layout',
                    content: '<div style="display:flex;gap:20px;flex-wrap:wrap"><div style="flex:1;min-width:180px;padding:16px">Left column</div><div style="flex:1;min-width:180px;padding:16px">Right column</div></div>',
                },
                {
                    id: 'heading',
                    label: 'Heading',
                    category: 'Content',
                    content: '<h2>Your heading</h2>',
                },
                {
                    id: 'text',
                    label: 'Text',
                    category: 'Content',
                    content: '<p>Write your text here.</p>',
                },
                {
                    id: 'image',
                    label: 'Image',
                    category: 'Content',
                    content: { type: 'image', style: { width: '100%', 'max-width': '480px' } },
                },
                {
                    id: 'button',
                    label: 'Button',
                    category: 'Content',
                    content: '<a href="#" style="display:inline-block;padding:12px 24px;background:#111827;color:#fff;text-decoration:none">Learn more</a>',
                },
                {
                    id: 'divider',
                    label: 'Divider',
                    category: 'Content',
                    content: '<hr>',
                },
                {
                    id: 'custom-html',
                    label: 'HTML block',
                    category: 'Advanced',
                    content: '<div class="custom-content">Custom content</div>',
                },
            ],
        },
        deviceManager: {
            devices: [
                { name: 'Desktop', width: '' },
                { name: 'Tablet', width: '768px', widthMedia: '992px' },
                { name: 'Mobile', width: '375px', widthMedia: '480px' },
            ],
        },
        traitManager: { appendTo: '#manpleasure-login-builder' },
        styleManager: {
            sectors: [
                {
                    name: 'Layout',
                    open: true,
                    properties: [
                        'display', 'flex-direction', 'justify-content',
                        'align-items', 'gap', 'width', 'min-height',
                        'margin', 'padding',
                    ],
                },
                {
                    name: 'Typography',
                    open: false,
                    properties: [
                        'font-family', 'font-size', 'font-weight',
                        'color', 'text-align', 'line-height', 'letter-spacing',
                    ],
                },
                {
                    name: 'Background',
                    open: false,
                    properties: [
                        'background-color', 'background-image',
                        'background-repeat', 'background-size',
                        'background-position',
                    ],
                },
                {
                    name: 'Borders',
                    open: false,
                    properties: [
                        'border', 'border-radius', 'box-shadow',
                    ],
                },
            ],
        },
        canvas: {
            styles: [],
        },
        ...(projectData ? { projectData } : {
            components: savedHtml || starterHtml,
            style: savedCss || starterCss,
        }),
    });

    const syncEditor = () => {
        document.getElementById('builder-html').value = editor.getHtml();
        document.getElementById('builder-css').value = editor.getCss();
        document.getElementById('builder-project-json').value =
            JSON.stringify(editor.getProjectData());
    };

    document.getElementById('save-draft-button')?.addEventListener('click', () => {
        syncEditor();
        document.getElementById('save-form').requestSubmit();
    });

    document.getElementById('publish-button')?.addEventListener('click', (event) => {
        const confirmed = window.confirm(
            'Publish the saved draft? Save Draft first if you have unsaved changes.'
        );

        if (!confirmed) {
            event.preventDefault();
        }
    });

    window.addEventListener('beforeunload', () => {
        // The browser handles navigation normally; drafts are saved explicitly.
    });
});
#!/usr/bin/env python3
"""
Redesign File Manager - Convert to modern UI with Font Awesome icons
Inspired by shadcn UI and ondo.finance design
"""

import re

# Read the original file
with open('kabfju1982381hjdcbja.php.backup-20251124-072131', 'r', encoding='utf-8') as f:
    content = f.read()

# Icon mappings: PHP SVG variable -> Font Awesome class
icon_replacements = {
    'dir': '<i class="fas fa-folder"></i>',
    'file': '<i class="fas fa-file-alt"></i>',
    'edit': '<i class="fas fa-edit"></i>',
    'rename': '<i class="fas fa-i-cursor"></i>',
    'chmod': '<i class="fas fa-shield-alt"></i>',
    'touch': '<i class="fas fa-clock"></i>',
    'download': '<i class="fas fa-download"></i>',
    'terminal': '<i class="fas fa-terminal"></i>',
    'home': '<i class="fas fa-home"></i>',
}

# Replace the $icons array in PHP
icons_php = "$icons=array("
for key, val in icon_replacements.items():
    icons_php += f"'{key}'=>'{val}',"
icons_php = icons_php.rstrip(',') + ");"

# Find and replace the icons array
pattern = r'\$icons=array\([^)]+\);'
content = re.sub(pattern, icons_php, content, flags=re.DOTALL)

# Modern CSS with compact design
modern_css = '''<style>
:root{
--bg-main:#0a0a0f;
--bg-card:rgba(18,18,24,0.85);
--bg-card-solid:#121218;
--bg-hover:rgba(139,92,246,0.12);
--bg-overlay:rgba(10,10,15,0.96);
--text-primary:#f4f4f5;
--text-secondary:#a1a1aa;
--text-muted:#71717a;
--border-color:rgba(63,63,70,0.4);
--accent-primary:#8b5cf6;
--accent-hover:#a78bfa;
--accent-glow:rgba(139,92,246,0.25);
--color-success:#10b981;
--color-error:#ef4444;
--color-warning:#f59e0b;
--color-info:#3b82f6;
--font-sans:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;
--font-mono:'JetBrains Mono','Courier New',monospace;
--radius:10px;
--radius-sm:6px;
--radius-lg:14px;
--shadow:0 2px 8px rgba(0,0,0,0.4),0 1px 3px rgba(0,0,0,0.3);
--shadow-md:0 4px 12px rgba(0,0,0,0.5),0 2px 6px rgba(0,0,0,0.35);
--shadow-lg:0 10px 25px rgba(0,0,0,0.6),0 4px 10px rgba(0,0,0,0.4);
--transition:0.15s cubic-bezier(0.4,0,0.2,1);
}
*{box-sizing:border-box;margin:0;padding:0}
body{
font-family:var(--font-sans);
background:linear-gradient(135deg,#0a0a0f 0%,#14141a 100%);
color:var(--text-primary);
line-height:1.5;
padding:12px;
font-size:13px;
min-height:100vh;
}
.container{max-width:1600px;margin:0 auto}
.card{
background:var(--bg-card);
backdrop-filter:blur(12px);
-webkit-backdrop-filter:blur(12px);
border:1px solid var(--border-color);
border-radius:var(--radius);
padding:14px 16px;
margin-bottom:12px;
box-shadow:var(--shadow);
transition:all var(--transition);
}
.card:hover{border-color:rgba(139,92,246,0.3)}
.server-info{
font-family:var(--font-mono);
font-size:11px;
line-height:1.6;
color:var(--text-secondary);
padding:10px 12px;
}
.server-info strong{color:var(--text-primary);font-weight:600;margin-right:4px}
.server-info .highlight{color:var(--accent-primary);font-weight:700}
.nav-bar{display:flex;align-items:center;gap:8px;margin:12px 0;flex-wrap:wrap}
.path-bar{
flex:1;
background:var(--bg-card);
backdrop-filter:blur(12px);
border:1px solid var(--border-color);
padding:6px 10px;
border-radius:var(--radius-sm);
font-size:12px;
word-break:break-all;
min-width:0;
font-family:var(--font-mono);
}
.path-root{
display:inline-flex;
align-items:center;
gap:4px;
font-weight:600;
margin-right:4px;
color:var(--accent-primary);
padding:2px 8px;
border-radius:var(--radius-sm);
background:var(--accent-glow);
transition:all var(--transition);
text-decoration:none;
font-size:11px;
}
.path-root:hover{background:rgba(139,92,246,0.35);transform:translateY(-1px)}
.path-root i{font-size:10px}
.path-custom{
color:var(--text-primary)!important;
background:rgba(139,92,246,0.2)!important;
font-weight:600!important;
border-radius:var(--radius-sm)!important;
padding:2px 8px!important;
margin:0 2px!important;
transition:all var(--transition);
}
.path-custom:hover{background:rgba(139,92,246,0.3)!important}
.path-status{
margin-left:6px;
font-weight:700;
font-size:9px;
text-transform:uppercase;
letter-spacing:0.5px;
padding:2px 6px;
border-radius:var(--radius-sm);
}
.path-status.writable{color:var(--color-success);background:rgba(16,185,129,0.15)}
.path-status.readable{color:var(--color-info);background:rgba(59,130,246,0.15)}
.path-status.denied{color:var(--color-error);background:rgba(239,68,68,0.15)}
.nav-buttons{display:flex;gap:6px}
.nav-buttons a,.nav-buttons button{
background:var(--bg-card);
backdrop-filter:blur(12px);
color:var(--text-primary);
padding:6px 10px;
border-radius:var(--radius-sm);
font-weight:500;
white-space:nowrap;
transition:all var(--transition);
border:1px solid var(--border-color);
cursor:pointer;
text-decoration:none;
font-size:12px;
display:inline-flex;
align-items:center;
gap:4px;
}
.nav-buttons a:hover,.nav-buttons button:hover{
background:var(--accent-primary);
color:#000;
border-color:var(--accent-primary);
transform:translateY(-2px);
box-shadow:0 4px 12px var(--accent-glow);
}
.nav-buttons i{font-size:11px}
.message{
padding:10px 14px;
margin-bottom:12px;
border-radius:var(--radius-sm);
border:1px solid;
font-weight:500;
font-size:12px;
animation:slideIn 0.3s;
background:var(--color-success);
border-color:var(--color-success);
color:#fff;
}
.message.error{background:var(--color-error);border-color:var(--color-error)}
@keyframes slideIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
.action-forms{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-start}
.form-group{display:flex;gap:6px;align-items:center}
.button,button,input,select{
font-family:var(--font-sans);
font-size:12px;
padding:6px 10px;
border:1px solid var(--border-color);
border-radius:var(--radius-sm);
background:var(--bg-main);
color:var(--text-primary);
transition:all var(--transition);
}
input[type=file]{padding:4px 8px;font-size:11px}
input[type=text]:focus,select:focus,textarea:focus{
outline:0;
border-color:var(--accent-primary);
box-shadow:0 0 0 3px var(--accent-glow);
}
.button,button{
background:var(--accent-primary);
color:#000;
border-color:var(--accent-primary);
cursor:pointer;
font-weight:600;
}
.button:hover,button:hover{
background:var(--accent-hover);
transform:translateY(-1px);
box-shadow:0 4px 12px var(--accent-glow);
}
.search-container{margin:12px 0}
.search-container input{
width:100%;
max-width:400px;
padding:8px 12px;
font-size:12px;
background:var(--bg-card);
backdrop-filter:blur(12px);
}
.file-table-container{
background:var(--bg-card);
backdrop-filter:blur(12px);
border:1px solid var(--border-color);
border-radius:var(--radius);
overflow:hidden;
box-shadow:var(--shadow-md);
}
.table-header-toolbar{
padding:10px 14px;
background:rgba(18,18,24,0.95);
border-bottom:1px solid var(--border-color);
display:flex;
align-items:center;
gap:10px;
}
.file-table{width:100%;border-collapse:collapse;table-layout:fixed}
.file-table td,.file-table th{
padding:8px 10px;
text-align:left;
vertical-align:middle;
border-bottom:1px solid rgba(63,63,70,0.25);
}
.file-table th{
background:rgba(18,18,24,0.98);
color:var(--text-secondary);
font-weight:700;
font-size:10px;
text-transform:uppercase;
letter-spacing:0.8px;
}
.file-table td{background:transparent;font-size:12px}
.file-table tr:hover td{
background:var(--bg-hover);
transition:all var(--transition);
}
.file-table tr.selected td{background:var(--bg-hover)}
.file-table tr.hidden{display:none}
.col-select{width:35px}
.col-name{width:36%}
.col-size{width:9%}
.col-owner{width:14%}
.col-perms{width:7%}
.col-date{width:13%}
.col-actions{width:11%}
.col-name>div{display:flex;align-items:center;gap:6px;min-width:0}
.col-name a{
color:var(--text-primary);
text-decoration:none;
white-space:nowrap;
overflow:hidden;
text-overflow:ellipsis;
flex:1;
min-width:0;
font-weight:500;
}
.col-name a:hover{color:var(--accent-primary)}
.col-name i{
width:14px;
font-size:13px;
color:var(--text-secondary);
flex-shrink:0;
}
.col-owner{
font-family:var(--font-mono);
font-size:10px;
color:var(--text-secondary);
word-break:break-word;
line-height:1.3;
}
.col-perms{font-family:var(--font-mono);font-size:11px}
.perms-writable{color:var(--color-success);font-weight:700}
.perms-readable{color:var(--color-info);font-weight:600}
.perms-denied{color:var(--color-error);font-weight:700}
.text-actions{
white-space:nowrap;
display:flex;
gap:3px;
align-items:center;
justify-content:flex-end;
}
.text-actions a,.text-actions button{
display:inline-flex;
align-items:center;
justify-content:center;
width:26px;
height:26px;
background:transparent;
border:1px solid transparent;
cursor:pointer;
color:var(--text-secondary);
border-radius:var(--radius-sm);
transition:all var(--transition);
text-decoration:none;
padding:0;
}
.text-actions a:hover,.text-actions button:hover{
color:var(--accent-primary);
background:var(--bg-hover);
border-color:var(--border-color);
transform:scale(1.1);
}
.text-actions i{font-size:11px}
#edit-view{display:none}
#edit-view textarea{
width:100%;
min-height:55vh;
font-family:var(--font-mono);
background:var(--bg-main);
color:var(--text-primary);
border-radius:var(--radius-sm);
border:1px solid var(--border-color);
padding:12px;
line-height:1.5;
font-size:12px;
resize:vertical;
}
.editor-info{
margin-bottom:10px;
font-size:10px;
color:var(--text-muted);
font-family:var(--font-mono);
padding:8px 10px;
background:rgba(139,92,246,0.08);
border-radius:var(--radius-sm);
border:1px solid rgba(139,92,246,0.2);
}
#modal-overlay{
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:var(--bg-overlay);
backdrop-filter:blur(8px);
z-index:999;
display:none;
align-items:center;
justify-content:center;
animation:fadeIn 0.2s;
}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
#modal-box{width:90%;max-width:480px}
#terminal-modal{
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:var(--bg-overlay);
backdrop-filter:blur(8px);
z-index:1000;
display:none;
align-items:center;
justify-content:center;
animation:fadeIn 0.2s;
}
#terminal-modal .card{
width:min(96vw,1100px);
max-height:92vh;
padding:0;
position:relative;
display:flex;
flex-direction:column;
}
.terminal-header{
padding:12px 16px;
border-bottom:1px solid var(--border-color);
display:flex;
justify-content:space-between;
align-items:center;
flex-shrink:0;
background:rgba(18,18,24,0.98);
}
.terminal-header h3{margin:0;font-size:14px;font-weight:700}
.terminal-close-btn{
background:var(--color-error);
color:#fff;
border:none;
padding:5px 12px;
border-radius:var(--radius-sm);
cursor:pointer;
font-weight:600;
font-size:11px;
transition:all var(--transition);
}
.terminal-close-btn:hover{background:#dc2626;transform:scale(1.05)}
.command-history{
max-height:110px;
overflow-y:auto;
padding:6px 10px;
background:#0d1117;
border-bottom:1px solid var(--border-color);
flex-shrink:0;
}
.history-item{
padding:4px 8px;
cursor:pointer;
border-radius:var(--radius-sm);
font-size:10px;
color:var(--text-muted);
margin:2px 0;
font-family:var(--font-mono);
transition:all var(--transition);
}
.history-item:hover{
background:rgba(139,92,246,0.2);
color:var(--text-primary);
}
.terminal-container{
font-family:var(--font-mono);
background:#0d1117;
border-radius:0 0 var(--radius) var(--radius);
flex:1;
display:flex;
flex-direction:column;
min-height:0;
}
.terminal-output{
flex:1;
overflow-y:auto;
padding:10px;
background:#0d1117;
white-space:pre-wrap;
word-break:break-word;
font-size:11px;
line-height:1.4;
color:#f0f6fc;
min-height:200px;
}
.terminal-input-container{
display:flex;
padding:8px 10px;
background:#161b22;
border-top:1px solid var(--border-color);
border-radius:0 0 var(--radius) var(--radius);
flex-shrink:0;
}
.terminal-input{
flex:1;
background:#0d1117;
color:#f0f6fc;
border:1px solid rgba(63,63,70,0.5);
padding:6px 10px;
font-family:var(--font-mono);
font-size:11px;
border-radius:var(--radius-sm);
}
.terminal-input:focus{
border-color:var(--accent-primary);
box-shadow:0 0 0 2px var(--accent-glow);
}
.terminal-send{margin-left:6px;padding:6px 12px;font-size:11px}
.context-menu{
position:fixed;
background:var(--bg-card);
backdrop-filter:blur(16px);
border:1px solid var(--border-color);
border-radius:var(--radius-sm);
padding:4px;
box-shadow:var(--shadow-lg);
z-index:1001;
min-width:150px;
display:none;
}
.context-menu-item{
padding:7px 10px;
cursor:pointer;
font-size:12px;
display:flex;
align-items:center;
gap:8px;
transition:all var(--transition);
border-radius:4px;
color:var(--text-primary);
}
.context-menu-item:hover{
background:var(--bg-hover);
color:var(--accent-primary);
}
.context-menu-item i{width:14px;font-size:12px;color:var(--text-secondary)}
.no-results{
text-align:center;
padding:30px;
color:var(--text-muted);
font-style:italic;
font-size:12px;
}
.top-control-bar{
display:flex;
justify-content:space-between;
align-items:center;
background:var(--bg-card);
backdrop-filter:blur(12px);
border:1px solid var(--border-color);
border-radius:var(--radius);
padding:10px 16px;
margin-bottom:12px;
box-shadow:var(--shadow-md);
}
.fm-branding{
font-size:14px;
font-weight:700;
color:var(--accent-primary);
text-shadow:0 0 20px var(--accent-glow);
}
.top-buttons{display:flex;gap:6px}
.top-buttons a{
background:var(--bg-main);
color:var(--text-primary);
padding:5px 10px;
border-radius:var(--radius-sm);
font-weight:600;
font-size:11px;
transition:all var(--transition);
border:1px solid var(--border-color);
cursor:pointer;
text-decoration:none;
display:inline-flex;
align-items:center;
gap:4px;
}
.top-buttons a:hover{
background:var(--accent-primary);
color:#000;
border-color:var(--accent-primary);
transform:translateY(-2px);
box-shadow:0 4px 12px var(--accent-glow);
}
.top-buttons i{font-size:10px}
.danger-btn{
background:var(--color-error)!important;
color:#fff!important;
border-color:var(--color-error)!important;
}
.danger-btn:hover{
background:#dc2626!important;
border-color:#dc2626!important;
color:#fff!important;
}
.command-container-three-col{
display:grid;
grid-template-columns:repeat(3,1fr);
gap:12px;
}
.execute-section,.navigate-section,.openfile-section{
display:flex;
flex-direction:column;
}
.command-container-three-col label{
font-size:10px;
font-weight:700;
color:var(--text-secondary);
margin-bottom:6px;
display:block;
text-transform:uppercase;
letter-spacing:0.5px;
}
.command-container-three-col button,.command-container-three-col input[type=text]{
font-family:var(--font-mono);
font-size:12px;
padding:7px 10px;
box-sizing:border-box;
border-radius:var(--radius-sm);
border:1px solid var(--border-color);
background:var(--bg-main);
color:var(--text-primary);
width:100%;
}
.command-container-three-col input[type=text]:focus{
border-color:var(--accent-primary);
box-shadow:0 0 0 3px var(--accent-glow);
outline:0;
}
.command-container-three-col button{
background:var(--accent-primary);
color:#000;
border-color:var(--accent-primary);
cursor:pointer;
font-weight:700;
margin-top:6px;
}
.command-container-three-col button:hover{
background:var(--accent-hover);
transform:translateY(-1px);
box-shadow:0 4px 12px var(--accent-glow);
}
.btn-small{
padding:4px 8px;
font-size:10px;
border-radius:var(--radius-sm);
border:1px solid var(--border-color);
background:var(--bg-main);
color:var(--text-primary);
cursor:pointer;
transition:all var(--transition);
font-weight:600;
}
.btn-small:hover{
background:var(--accent-primary);
color:#000;
border-color:var(--accent-primary);
transform:translateY(-1px);
}
.btn-small.danger{background:var(--color-error);color:#fff;border-color:var(--color-error)}
.btn-small.danger:hover{background:#dc2626;border-color:#dc2626}
.clear-output-btn{
position:absolute;
top:6px;
right:6px;
background:var(--color-error);
color:#fff;
border:none;
padding:4px 8px;
border-radius:var(--radius-sm);
font-size:9px;
cursor:pointer;
opacity:0.8;
transition:all var(--transition);
font-weight:600;
}
.clear-output-btn:hover{opacity:1;transform:scale(1.05)}
@media (max-width:768px){
.container{padding:6px}
body{padding:8px}
.action-forms{flex-direction:column}
.nav-bar{flex-direction:column;align-items:stretch}
.file-table{font-size:11px}
.file-table td,.file-table th{padding:6px 8px}
.col-date,.col-owner{display:none}
.col-name{width:52%}
.col-size{width:14%}
.col-perms{width:13%}
.col-actions{width:21%}
.command-container-three-col{
grid-template-columns:1fr;
gap:10px;
}
}
</style>'''

# Replace the old CSS
css_pattern = r'<style>.*?</style>'
content = re.sub(css_pattern, modern_css, content, flags=re.DOTALL)

# Replace emoji in context menu with Font Awesome
content = content.replace("{ action: 'copy', text: 'Copy', icon: '📋' }",
                          "{ action: 'copy', text: 'Copy', icon: '<i class=\"fas fa-copy\"></i>' }")
content = content.replace("{ action: 'move', text: 'Move', icon: '✂️' }",
                          "{ action: 'move', text: 'Move', icon: '<i class=\"fas fa-cut\"></i>' }")
content = content.replace("{ action: 'delete', text: 'Delete', icon: '🗑️' }",
                          "{ action: 'delete', text: 'Delete', icon: '<i class=\"fas fa-trash-alt\"></i>' }")

# Update title
content = content.replace('Enhanced File Manager - Stable Final',
                          'File Manager Pro - Modern Edition')

# Write the redesigned version
with open('kabfju1982381hjdcbja.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("✓ Redesign complete!")
print("✓ Modern CSS applied (shadcn UI + ondo.finance inspired)")
print("✓ All icons converted to Font Awesome")
print("✓ Layout optimized for compact view")
print("✓ Glassmorphism effects added")

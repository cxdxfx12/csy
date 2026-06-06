/**
 * CHM Builder - Uses Microsoft hhc.exe (already cloned to _hhw/)
 */
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const SRC_DIR = path.join(__dirname, 'chm_src');
const HHW_DIR = path.join(__dirname, '_hhw', 'HTML Help Workshop');
const HHC_EXE = path.join(HHW_DIR, 'hhc.exe');

// Register DLLs
try {
    execSync(`regsvr32 /s "${path.join(HHW_DIR, 'itircl.dll')}"`, { stdio: 'ignore' });
    execSync(`regsvr32 /s "${path.join(HHW_DIR, 'itcc.dll')}"`, { stdio: 'ignore' });
    console.log('DLLs registered');
} catch(e) {}

// Delete old output
const outChm = path.join(__dirname, 'AI编程大师课.chm');
try { fs.unlinkSync(outChm); } catch(e) {}
try { fs.unlinkSync(path.join(SRC_DIR, 'ai_coding_master.chm')); } catch(e) {}

// Write .hhp file (ANSI/GBK encoded)
const hhp = `[OPTIONS]
Compatibility=1.1 or later
Compiled file=ai_coding_master.chm
Contents file=ai_coding_master.hhc
Default Window=main
Default topic=index.html
Display compile progress=Yes
Full-text search=Yes
Language=0x804
Title=AI Biancheng Dashi Ke

[FILES]
index.html
intro.html
ch1_asking.html
ch2_architecture.html
ch3_coding.html
ch4_debugging.html
ch5_project.html
ch6_best.html
faq.html
style.css

[WINDOWS]
main="AI Biancheng Dashi Ke","ai_coding_master.hhc",,"index.html","index.html",,,,,0x20,150,0x104E,[0,0,1000,700],,,,,,,0

[INFOTYPES]
`;
fs.writeFileSync(path.join(SRC_DIR, 'ai_coding_master.hhp'), hhp, 'ascii');

// Write .hhc file (GBK encoding for Chinese chars)
const iconv = require('iconv-lite');
const hhcUtf8 = `<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN">
<HTML>
<HEAD>
<meta http-equiv="Content-Type" content="text/html; charset=gb2312">
<meta name="GENERATOR" content="Microsoft HTML Help Workshop 4.1">
</HEAD>
<BODY>
<OBJECT type="text/site properties">
    <param name="ImageType" value="Folder">
</OBJECT>
<UL>
    <LI><OBJECT type="text/sitemap">
        <param name="Name" value="封面 - AI编程大师课">
        <param name="Local" value="index.html">
    </OBJECT>
    <LI><OBJECT type="text/sitemap">
        <param name="Name" value="引言：AI编程新时代">
        <param name="Local" value="intro.html">
    </OBJECT>
    <LI><OBJECT type="text/sitemap">
        <param name="Name" value="第1章 - 提问的艺术">
        <param name="Local" value="ch1_asking.html">
    </OBJECT>
    <LI><OBJECT type="text/sitemap">
        <param name="Name" value="第2章 - 架构设计">
        <param name="Local" value="ch2_architecture.html">
    </OBJECT>
    <LI><OBJECT type="text/sitemap">
        <param name="Name" value="第3章 - 高效编码">
        <param name="Local" value="ch3_coding.html">
    </OBJECT>
    <LI><OBJECT type="text/sitemap">
        <param name="Name" value="第4章 - Bug终结者">
        <param name="Local" value="ch4_debugging.html">
    </OBJECT>
    <LI><OBJECT type="text/sitemap">
        <param name="Name" value="第5章 - 项目管理">
        <param name="Local" value="ch5_project.html">
    </OBJECT>
    <LI><OBJECT type="text/sitemap">
        <param name="Name" value="第6章 - 最佳实践">
        <param name="Local" value="ch6_best.html">
    </OBJECT>
    <LI><OBJECT type="text/sitemap">
        <param name="Name" value="常见问题 FAQ">
        <param name="Local" value="faq.html">
    </OBJECT>
</UL>
</BODY>
</HTML>
`;
const hhcGbk = iconv.encode(hhcUtf8, 'gbk');
fs.writeFileSync(path.join(SRC_DIR, 'ai_coding_master.hhc'), hhcGbk);
console.log('Project files written (GBK encoding)');

// Compile
console.log('Compiling with hhc.exe...');
try {
    const result = execSync(`"${HHC_EXE}" ai_coding_master.hhp`, {
        cwd: SRC_DIR,
        encoding: 'buffer',
        timeout: 30000
    });
    console.log(result.toString());
} catch (e) {
    console.log(e.stdout ? e.stdout.toString() : '');
    console.error(e.stderr ? e.stderr.toString() : '');
}

// Check output
const builtPath = path.join(SRC_DIR, 'ai_coding_master.chm');
if (fs.existsSync(builtPath)) {
    const size = fs.statSync(builtPath).size;
    console.log(`\nSUCCESS! Size: ${(size/1024).toFixed(1)} KB`);
    // Copy to output with Chinese name
    fs.copyFileSync(builtPath, outChm);
    console.log(`Copied to: ${outChm}`);
} else {
    console.error('Compilation failed - no output file');
    process.exit(1);
}

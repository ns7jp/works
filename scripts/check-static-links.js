const fs = require("fs");
const path = require("path");

const root = process.cwd();
const htmlFiles = fs.readdirSync(root).filter((file) => file.endsWith(".html"));
const failures = [];

function shouldSkip(link) {
  return (
    !link ||
    link.startsWith("#") ||
    link.startsWith("http://") ||
    link.startsWith("https://") ||
    link.startsWith("mailto:") ||
    link.startsWith("tel:") ||
    link.startsWith("data:") ||
    link.startsWith("//") ||
    link.startsWith("javascript:")
  );
}

function stripQuery(link) {
  return link.split("?")[0];
}

function fileHasAnchor(filePath, anchor) {
  if (!anchor) return true;
  const html = fs.readFileSync(filePath, "utf8");
  const escaped = anchor.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  const idPattern = new RegExp(`\\b(id|name)=["']${escaped}["']`);
  return idPattern.test(html);
}

for (const file of htmlFiles) {
  const filePath = path.join(root, file);
  const html = fs.readFileSync(filePath, "utf8").replace(/<!--[\s\S]*?-->/g, "");
  const matches = html.matchAll(/\b(?:href|src)=["']([^"']+)["']/g);

  for (const match of matches) {
    const rawLink = match[1].trim();
    if (shouldSkip(rawLink)) continue;

    const [targetPart, anchor] = stripQuery(rawLink).split("#");
    if (!targetPart) continue;

    const normalized = decodeURIComponent(targetPart);
    const targetPath = normalized.startsWith("/")
      ? path.resolve(root, normalized.slice(1))
      : path.resolve(path.dirname(filePath), normalized);

    if (!targetPath.startsWith(root)) {
      failures.push(`${file}: ${rawLink} points outside the site root`);
      continue;
    }

    if (!fs.existsSync(targetPath)) {
      failures.push(`${file}: ${rawLink} is missing`);
      continue;
    }

    if (anchor && targetPath.endsWith(".html") && !fileHasAnchor(targetPath, anchor)) {
      failures.push(`${file}: ${rawLink} anchor is missing`);
    }
  }
}

if (failures.length > 0) {
  console.error("Static link check failed:");
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log(`Checked ${htmlFiles.length} HTML files. Local links look good.`);

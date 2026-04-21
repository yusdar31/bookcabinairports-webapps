# Konfigurasi MCP untuk Codex, OpenCode, dan Antigravity

Dokumen ini menyiapkan contoh konfigurasi MCP lokal untuk server Bookcabin yang sudah dibuild di:

`D:\Project AWS\Bandara\mcp\dist\index.js`

## Prasyarat

Pastikan langkah ini sudah selesai:

```bash
cd mcp
npm install
npm run build
```

## Command server yang dipakai bersama

Semua agent sebaiknya diarahkan ke command stdio yang sama:

```bash
node D:\Project AWS\Bandara\mcp\dist\index.js
```

## Codex

Rute yang paling aman untuk Codex adalah mendaftarkan MCP lewat CLI, karena format ini memang didokumentasikan resmi untuk Codex.

### Opsi 1: lewat CLI

```bash
codex mcp add bookcabin-project -- node D:\Project AWS\Bandara\mcp\dist\index.js
```

Lalu verifikasi:

```bash
codex mcp list
```

### Opsi 2: lewat `~/.codex/config.toml`

Contoh berikut adalah penyesuaian lokal berbasis pola konfigurasi Codex yang resmi mendukung `config.toml`.

```toml
[mcp_servers.bookcabin-project]
command = "node"
args = ["D:\\Project AWS\\Bandara\\mcp\\dist\\index.js"]
```

Jika instalasi Codex kamu hanya menerima penambahan lewat CLI, pakai opsi 1.

## OpenCode

OpenCode mendokumentasikan konfigurasi MCP di file config dengan key `mcp`. Contoh lokal untuk project ini:

```json
{
  "$schema": "https://opencode.ai/config.json",
  "mcp": {
    "bookcabin-project": {
      "type": "local",
      "command": "node",
      "args": [
        "D:\\Project AWS\\Bandara\\mcp\\dist\\index.js"
      ],
      "enabled": true
    }
  }
}
```

Kalau instalasi OpenCode kamu memakai file config yang sudah ada, cukup merge blok `mcp.bookcabin-project` ke file tersebut.

## Antigravity

Di Antigravity, jalurnya biasanya:

1. Buka panel agent
2. Pilih `...`
3. `Manage MCP Servers`
4. `View raw config`

Tambahkan server ini ke `mcp_config.json`:

```json
{
  "mcpServers": {
    "bookcabin-project": {
      "command": "node",
      "args": [
        "D:\\Project AWS\\Bandara\\mcp\\dist\\index.js"
      ],
      "env": {}
    }
  }
}
```

Kalau file itu sudah berisi server lain, merge saja isi `mcpServers` tanpa menghapus yang lama.

## Prompt awal yang disarankan

Setelah MCP terhubung, minta ketiga agent memulai dengan pola ini:

```text
Gunakan MCP bookcabin-project lebih dulu. Baca project overview dan known gaps sebelum mengubah code.
```

## Catatan

- MCP ini saat ini bersifat read-only
- Aman dipakai untuk bug triage, feature planning, dan UI/UX exploration
- Belum ada tool yang melakukan write ke database, deploy, atau Terraform

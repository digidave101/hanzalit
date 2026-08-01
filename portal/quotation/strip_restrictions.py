#!/usr/bin/env python3
"""
strip_restrictions.py — Remove owner/print restrictions from all PDFs
Run via SSH: python3 strip_restrictions.py

Upload to: public_html/portal/quotation/
Then run:  cd ~/public_html/portal/quotation && python3 strip_restrictions.py
"""

import os, sys, glob

# ── Install pikepdf if needed ──────────────────────────────────────────────
try:
    import pikepdf
except ImportError:
    print("Installing pikepdf...")
    os.system(f"{sys.executable} -m pip install pikepdf --break-system-packages --quiet")
    import pikepdf

# ── Config ─────────────────────────────────────────────────────────────────
BASE_DIR    = os.path.dirname(os.path.abspath(__file__))
FLYERS_DIR  = os.path.join(BASE_DIR, 'flyers')
CURRIC_DIR  = os.path.join(BASE_DIR, 'curriculum')
BACKUP_EXT  = '.restricted_backup'   # original files kept with this extension

def strip_folder(folder_path: str, label: str):
    """Strip owner restrictions from every PDF in folder_path."""
    pdfs = sorted(glob.glob(os.path.join(folder_path, '*.pdf')))
    if not pdfs:
        print(f"\n{label}: No PDFs found in {folder_path}")
        return

    ok = 0; skipped = 0; errors = []
    print(f"\n{label}: {len(pdfs)} files in {folder_path}")
    print("─" * 60)

    for i, path in enumerate(pdfs, 1):
        name = os.path.basename(path)
        backup = path + BACKUP_EXT

        try:
            with pikepdf.open(path, allow_overwriting_input=False) as pdf:
                # Check if it has any restrictions
                has_restrict = False
                if '/Encrypt' in pdf.trailer:
                    has_restrict = True

                if not has_restrict:
                    # No restrictions — skip (don't waste time rewriting)
                    print(f"[{i}/{len(pdfs)}] SKIP  {name} (no restrictions)")
                    skipped += 1
                    continue

                # Save backup of original (once)
                if not os.path.exists(backup):
                    import shutil
                    shutil.copy2(path, backup)

                # Re-save without encryption/restrictions
                # pikepdf strips all owner password restrictions on save
                tmp = path + '.stripping'
                pdf.save(tmp, encryption=False)

            # Atomic replace
            os.replace(tmp, path)
            size_kb = round(os.path.getsize(path) / 1024)
            print(f"[{i}/{len(pdfs)}] OK    {name} ({size_kb}KB, restrictions removed)")
            ok += 1

        except pikepdf.PasswordError:
            # User password set — truly locked, can't open at all
            print(f"[{i}/{len(pdfs)}] LOCK  {name} (user password — cannot unlock)")
            errors.append(f"{name}: user password locked")
        except Exception as e:
            print(f"[{i}/{len(pdfs)}] ERROR {name} — {e}")
            errors.append(f"{name}: {e}")
            # Clean up temp if it exists
            tmp = path + '.stripping'
            if os.path.exists(tmp):
                os.remove(tmp)

    print(f"\n{'─'*60}")
    print(f"  Stripped: {ok}  |  Skipped (clean): {skipped}  |  Errors: {len(errors)}")
    if errors:
        print("\n  Errors:")
        for e in errors:
            print(f"    - {e}")
    return ok, skipped, errors

# ── Main ────────────────────────────────────────────────────────────────────
print("=" * 60)
print("PDF Restriction Stripper")
print("=" * 60)

total_ok = 0
total_err = []

for folder, label in [(FLYERS_DIR, "FLYERS"), (CURRIC_DIR, "CURRICULUM")]:
    if os.path.isdir(folder):
        ok, skipped, errs = strip_folder(folder, label)
        total_ok += ok
        total_err += errs
    else:
        print(f"\n{label}: Folder not found — {folder}")

print("\n" + "=" * 60)
print(f"COMPLETE — {total_ok} files stripped, {len(total_err)} errors")
print("You can now run: php combine_docs.php")
print("=" * 60)

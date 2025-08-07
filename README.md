
Each entry includes a linked username and relevant metadata for easy auditing.

---

## 💡 Use Cases

- Track add-on changes made by other admins
- Debug issues introduced by specific upgrades or installs
- Monitor site configuration changes over time

---

## ⚠️ Limitations

- **XenForo core upgrades are not tracked**, as they don’t trigger add-on lifecycle events.
- CLI-based installs, upgrades, and uninstalls are not logged (by XenForo design).

---

## 🛠️ Developer Notes

This add-on listens to XenForo’s native `addon_post_*` events for GUI-based add-on lifecycle events and overrides the `actionToggle()` controller method to track enable/disable actions.

---

## 📦 Installation

1. Upload the contents of the `upload/` directory to your XenForo root.
2. Install the add-on via Admin > Add-ons.

---

## 🧾 License

MIT License — use it freely in your projects.

---

## 👤 Author

Maintained by [Wutime](https://github.com/Wutime).

# Queue Processing Setup for Shared Hosting

## 🎯 **Quick Decision Guide**

**For Small Apps (< 100 emails/day):**  
→ Use **Option 1: Sync Processing** (simplest)

**For Medium Apps (100-1000 emails/day):**  
→ Use **Option 2: HTTP Endpoint + Cron**

**For Large Apps (1000+ emails/day):**  
→ Use **Option 3: Standalone Script** or consider VPS

---

## 📋 **Setup Instructions**

### **Option 1: Immediate Processing (Recommended for Most Cases)**
1. Set in production `.env`: `QUEUE_CONNECTION=sync`
2. That's it! Emails send immediately when triggered

### **Option 2: HTTP Endpoint + Cron Job**
1. Set in production `.env`:
   ```env
   QUEUE_CONNECTION=database
   QUEUE_CRON_TOKEN=your-random-token-here-make-it-long
   ```
2. In cPanel/Hosting Panel, add cron job:
   ```bash
   * * * * * curl -X POST "https://yourdomain.com/api/queue/process" -H "X-Cron-Token: your-token-here" >/dev/null 2>&1
   ```

### **Option 3: PHP Script + Cron Job**  
1. Upload `queue-worker.php` to your server root
2. Set in production `.env`: `QUEUE_CONNECTION=database`
3. In cPanel/Hosting Panel, add cron job:
   ```bash
   * * * * * /usr/bin/php /path/to/your/project/queue-worker.php >/dev/null 2>&1
   ```

---

## 🔧 **Testing Your Setup**

### Test the HTTP Endpoint:
```bash
# Replace with your domain and token
curl -X POST "https://yourdomain.com/api/queue/process" \
  -H "X-Cron-Token: your-token"
```

### Check Queue Status:
```bash
# In Laravel Tinker or create a test endpoint
php artisan queue:work --once
```

---

## 📊 **Monitoring & Logs**

- **Sync**: Check `storage/logs/laravel.log`
- **HTTP Endpoint**: Monitor Laravel logs + web server logs  
- **PHP Script**: Check `storage/logs/queue-worker.log`

---

## ⚡ **Performance Tips**

1. **Use database queues** for shared hosting (not Redis/SQS)
2. **Run cron every minute** for responsive email delivery
3. **Monitor failed jobs** in `failed_jobs` database table
4. **Set proper timeouts** to avoid cron job conflicts

---

## 🚨 **Security Notes**

- Keep your `QUEUE_CRON_TOKEN` secret and long (32+ characters)
- Use HTTPS for the HTTP endpoint option
- Restrict access to `queue-worker.php` via `.htaccess` if needed
- Monitor logs for unauthorized access attempts

# Bilet Satın Alma Platformu

## Kurulum

```bash
git clone https://github.com/eray-atalay/bilet-satin-alma.git
```

```bash
cd bilet-satin-alma
```

```bash
docker-compose up -d
```

`http://localhost:8080/db_setup.php`

`http://localhost:8080/`

---

## Varsayılan Kullanıcılar

`db_setup.php` betiği çalıştırıldığında, sistemi test etmeniz için aşağıdaki 3 varsayılan kullanıcı otomatik olarak oluşturulur:

* **Rol:** Site Admini
    * **Kullanıcı Adı:** `admin`
    * **Şifre:** `admin`

* **Rol:** Firma Yetkilisi (Varsayılan Firma'ya atanmış)
    * **Kullanıcı Adı:** `firma`
    * **Şifre:** `firma`

* **Rol:** Genel Kullanıcı
    * **Kullanıcı Adı:** `eray`
    * **Şifre:** `eray`

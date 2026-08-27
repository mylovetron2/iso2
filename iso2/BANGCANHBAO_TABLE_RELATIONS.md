# Bang canh bao - So do hinh anh va ban in A4 (2 mat)

Tai lieu nay duoc dong goi de in 2 mat A4:
- Mat 1: Mo hinh du lieu + mo hinh nhan dang
- Mat 2: Mo hinh xu ly + mo hinh loi ghi de + checklist kiem soat

Huong dan in nhanh:
1. In kho giay A4
2. Chon in 2 mat (flip on long edge)
3. Scale 100% va margin narrow

---

## Mat 1 - Mo hinh du lieu va nhan dang

### 1) So do du lieu (ERD)

```mermaid
erDiagram
        thietbihckd_iso {
                int stt PK
                varchar mavattu
                varchar tenviettat
                varchar somay
                varchar tenthietbi
                varchar bophansh
                varchar chusohuu
        }

        kehoach_iso {
                int stt PK
                varchar thang
                varchar namkh
                varchar tenthietbi
                varchar somay
        }

        hosohckd_iso {
                int stt PK
                int thietbi_stt FK
                varchar tenmay
                varchar sohs
                date ngayhc
                date ngayhctt
                varchar nhanvien
                varchar ttkt
        }

        donvi_iso {
                varchar madv PK
                varchar tendv
        }

        thietbihckd_iso ||--o{ hosohckd_iso : "stt -> thietbi_stt (uu tien)"
        thietbihckd_iso ||--o{ kehoach_iso : "join theo tenthietbi + somay"
        thietbihckd_iso }o--|| donvi_iso : "bophansh -> madv"
```

### 2) So do nhan dang khoa (Identity Model)

```mermaid
flowchart LR
        A[mavattu / tenmay] --> B{co the trung}
        B --> C[khong dung de update]

        D[thietbihckd_iso.stt] --> E[dinh danh may vat ly]
        E --> F[dung de chon dung may]

        G[hosohckd_iso.stt] --> H[dinh danh ho so]
        H --> I[uu tien cao nhat khi update]
```

Quy tac chuan:
- Uu tien 1: hoso_stt
- Uu tien 2: thietbi_stt + ngayhc
- Khong update theo mavattu/tenmay don le

---

## Mat 2 - Mo hinh xu ly va kiem soat loi

### 3) So do luong xu ly (Open -> Save)

```mermaid
sequenceDiagram
        participant U as User
        participant R as bangcanhbao.php
        participant C as BangCanhBaoController
        participant T as ThietBiHCKD
        participant H as HoSoHCKD
        participant DB as MySQL

        U->>R: formhoso(mavattu, stt)
        R->>C: formHoSo()
        C->>T: findById(stt) uu tien
        T->>DB: SELECT thietbihckd_iso by stt
        DB-->>T: may vat ly
        C-->>U: form_hoso + hidden(thietbi_stt, hoso_stt)

        U->>R: POST savehoso
        R->>C: saveHoSo()
        C->>H: saveHoSo(data)
        H->>H: co hoso_stt -> findById
        H->>H: khong co -> tim thietbi_stt + ngayhc
        H->>DB: UPDATE/INSERT hosohckd_iso
        DB-->>H: ket qua
        H-->>C: success/error
        C-->>U: redirect + thong bao
```

### 4) So do loi ghi de da gap (Overwrite Model)

```mermaid
flowchart TD
        A[Nhap may DL/60-820] --> B[Luu tenmay=DL/60]
        B --> C[Nhap may DL/60 khac]
        C --> D[Tim theo tenmay=DL/60]
        D --> E[Cap nhat nham ban ghi]
        E --> F[Du lieu may 820 bi de]
```

### 5) Checklist van hanh truoc khi luu

| Muc kiem | Dat | Khong dat |
|---|---|---|
| Co thietbi_stt trong form | [ ] | [ ] |
| Co hoso_stt khi sua ho so | [ ] | [ ] |
| Khong dung mavattu don le de update | [ ] | [ ] |
| Dieu kien tim ho so la thietbi_stt + ngayhc | [ ] | [ ] |
| Thong bao loi hien ro (khong generic false) | [ ] | [ ] |

### 6) Ket luan

Neu giu dung 3 mo hinh ben tren:
- Data model
- Identity model
- Process model

thi cac may cung model nhu DL/60 se khong con ghi de du lieu len nhau.

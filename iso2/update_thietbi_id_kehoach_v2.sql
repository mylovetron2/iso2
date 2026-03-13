-- Cập nhật thietbi_id vào bảng ke_hoach_bao_duong_dinh_ky_iso
-- Mapping từ STT DB (thietbi_iso.stt) vào thietbi_id
-- Dựa trên kết quả từ find_thietbi_stt.php với logic mới: match cả ten_thietbi VÀ so_serial
-- Loại trừ các STT: 71, 72, 73, 74, 75, 77, 80, 89, 90, 91, 100-107, 113, 115, 116, 118, 121, 123-125, 143, 145, 161-165, 174, 228, 243, 249

-- Bước 1: Reset tất cả thietbi_id về NULL
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = NULL;

-- Bước 2: Cập nhật thietbi_id cho các bản ghi cụ thể
-- Records 1-70
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 164 WHERE id = 2033; -- STT 1: GTET 11533904
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 163 WHERE id = 2034; -- STT 2: GTET 11705762
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 162 WHERE id = 2035; -- STT 3: GTET 11705765
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 182 WHERE id = 2036; -- STT 4: IDT 11680456
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 181 WHERE id = 2037; -- STT 5: IDT 11680458
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 168 WHERE id = 2038; -- STT 6: DSNT 11534471
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 169 WHERE id = 2039; -- STT 7: DSNT 11534475
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 167 WHERE id = 2040; -- STT 8: DSNT 11660710
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 166 WHERE id = 2041; -- STT 9: DSNT 11660711
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 177 WHERE id = 2045; -- STT 10: BSAT 11310050
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 174 WHERE id = 2046; -- STT 11: BSAT 11603269
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 183 WHERE id = 2047; -- STT 12: BHPT 12009522
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 184 WHERE id = 2048; -- STT 13: BHPT 12225262
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 179 WHERE id = 2049; -- STT 14: ICT 11660551
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 178 WHERE id = 2050; -- STT 15: ICT 11660552
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 187 WHERE id = 2051; -- STT 16: ACRT 12068675
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 185 WHERE id = 2052; -- STT 17: ACRT 12068676
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 970 WHERE id = 2056; -- STT 18: D4TG 117
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 872 WHERE id = 2058; -- STT 19: D4TG 967
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1291 WHERE id = 2059; -- STT 20: D4TG 505
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 132 WHERE id = 2064; -- STT 21: SDDT 762
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 133 WHERE id = 2065; -- STT 22: SDDT 763
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 112 WHERE id = 2068; -- STT 23: DSNT 208
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 113 WHERE id = 2069; -- STT 24: DSNT 209
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 104 WHERE id = 2072; -- STT 25: SDLT 93
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 105 WHERE id = 2073; -- STT 26: SDLT 94
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 121 WHERE id = 2074; -- STT 27: BCDT 33
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 122 WHERE id = 2075; -- STT 28: BCDT 34
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 125 WHERE id = 2076; -- STT 29: BCDT 35
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 124 WHERE id = 2077; -- STT 30: BCDT 361
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 134 WHERE id = 2078; -- STT 31: FIAC 34
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 136 WHERE id = 2079; -- STT 32: FIAC 93
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 100 WHERE id = 2080; -- STT 33: HRI 143
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 101 WHERE id = 2081; -- STT 34: HRI 144
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 507 WHERE id = 2084; -- STT 35: MSFL 4
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 76 WHERE id = 2085; -- STT 36: CAST-V 26
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 77 WHERE id = 2086; -- STT 37: CAST-V 27
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 78 WHERE id = 2087; -- STT 38: CAST-V 113
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 79 WHERE id = 2088; -- STT 39: CAST-V 114
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 650 WHERE id = 2089; -- STT 40: CAST-F 703
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 864 WHERE id = 2090; -- STT 41: CAST-F 565
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 75 WHERE id = 2091; -- STT 42: CAST-F 874
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 80 WHERE id = 2092; -- STT 43: CSNG 28
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 81 WHERE id = 2093; -- STT 44: CSNG 29
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 82 WHERE id = 2094; -- STT 45: CSNG 90
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 83 WHERE id = 2095; -- STT 46: CSNG 91
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 228 WHERE id = 2143; -- STT 47: LDS/MFS 0716
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 229 WHERE id = 2144; -- STT 48: LDS/MFS 0717
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 250 WHERE id = 2159; -- STT 49: JSCC JSCC-0802
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 249 WHERE id = 2160; -- STT 50: JSCC 801
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1168 WHERE id = 2189; -- STT 51: iCCL 6
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1167 WHERE id = 2190; -- STT 52: iGS 5
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1562 WHERE id = 2191; -- STT 53: iCT 7
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1563 WHERE id = 2192; -- STT 54: iCT 8
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1164 WHERE id = 2203; -- STT 55: iBT 6
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1163 WHERE id = 2204; -- STT 56: iBT 5
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1166 WHERE id = 2206; -- STT 57: iSL 5
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 722 WHERE id = 2268; -- STT 58: ГГК 3
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 556 WHERE id = 2269; -- STT 59: ГГК 4
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 623 WHERE id = 2270; -- STT 60: ГГК 1007
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 622 WHERE id = 2271; -- STT 61: ГГК 1008
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 721 WHERE id = 2272; -- STT 62: ГГК 1009
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 557 WHERE id = 2273; -- STT 63: ГГК 1012
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 699 WHERE id = 2279; -- STT 64: MБK 1
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 578 WHERE id = 2280; -- STT 65: MБK 3
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 701 WHERE id = 2281; -- STT 66: MБK 1025
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 579 WHERE id = 2282; -- STT 67: MБK 1038
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 638 WHERE id = 2283; -- STT 68: MБK 1039
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 700 WHERE id = 2284; -- STT 69: MБK 1040
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 703 WHERE id = 2285; -- STT 70: MБK 1041

-- STT 71-73: LOẠI TRỪ (Connector Sub, ГК-60)
-- STT 74-75: LOẠI TRỪ (ГК-60)

-- Record 76
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 565 WHERE id = 2236; -- STT 76: ГК-60 1004

-- STT 77: LOẠI TRỪ (БК3-60)

-- Records 78-79
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 552 WHERE id = 2238; -- STT 78: БК3-60 1002
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 553 WHERE id = 2239; -- STT 79: БК3-60 1003

-- STT 80: LOẠI TRỪ (БК3-60)

-- Records 81-88
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 512 WHERE id = 2241; -- STT 81: ДЛ-60 820
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 655 WHERE id = 2242; -- STT 82: ДЛ-60 822
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 59 WHERE id = 2243; -- STT 83: ДЛ-60 823
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 574 WHERE id = 2245; -- STT 84: ДЛ-60 825
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 60 WHERE id = 2246; -- STT 85: ДЛ-60 826
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 515 WHERE id = 2247; -- STT 86: ДЛ-60 827
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 575 WHERE id = 2248; -- STT 87: ДЛ-60 828
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 488 WHERE id = 2249; -- STT 88: ДЛ-60 829

-- STT 89-91: LOẠI TRỪ (ГК-76)

-- Records 92-99
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 527 WHERE id = 2253; -- STT 92: ГК-76 1025
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 626 WHERE id = 2254; -- STT 93: ГК-76 1026
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 562 WHERE id = 2255; -- STT 94: ГК-76 1028
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 712 WHERE id = 2256; -- STT 95: ГК-76 1045
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 499 WHERE id = 2257; -- STT 96: ГК-76 1046
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 629 WHERE id = 2258; -- STT 97: HHK-76 7
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 719 WHERE id = 2259; -- STT 98: HHK-76 8
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 628 WHERE id = 2260; -- STT 99: HHK-76 1009

-- STT 100-107: LOẠI TRỪ (HHK-76, ИK-76)

-- Records 108-112
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 635 WHERE id = 2275; -- STT 108: ИK-76 1017
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 693 WHERE id = 2276; -- STT 109: ИK-76 1030
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 569 WHERE id = 2277; -- STT 110: ИK-76 1031
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 636 WHERE id = 2278; -- STT 111: ИK-76 1032
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 694 WHERE id = 2286; -- STT 112: БК3-76 1035

-- STT 113: LOẠI TRỪ (БК3-76)

-- Record 114
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 551 WHERE id = 2288; -- STT 114: БК3-76 1038

-- STT 115-116: LOẠI TRỪ (БК3-76)

-- Record 117
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 625 WHERE id = 2291; -- STT 117: БК3-76 1041

-- STT 118: LOẠI TRỪ (CKП-76)

-- Records 119-120
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 561 WHERE id = 2293; -- STT 119: CKП-76 1033
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 709 WHERE id = 2294; -- STT 120: CKП-76 1034

-- STT 121: LOẠI TRỪ (CKП-76)

-- Record 122
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 68 WHERE id = 2296; -- STT 122: CKП-76 1036

-- STT 123-125: LOẠI TRỪ (CKП-76)

-- Record 126
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 47 WHERE id = 2300; -- STT 126: CKП-76 1044

-- Records 127-142
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 621 WHERE id = 2301; -- STT 127: ДЛ-76 554
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 620 WHERE id = 2302; -- STT 128: ДЛ-76 562
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 572 WHERE id = 2303; -- STT 129: ДЛ-76 563
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 573 WHERE id = 2304; -- STT 130: ДЛ-76 608
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 486 WHERE id = 2305; -- STT 131: ДЛ-76 610
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 487 WHERE id = 2306; -- STT 132: ДЛ-76 613(696)
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 35 WHERE id = 2309; -- STT 133: ГК-A-90 1004
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 36 WHERE id = 2310; -- STT 134: ГК-A-90 1005
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 30 WHERE id = 2311; -- STT 135: ГК + ЛМ -A-90 1001
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 31 WHERE id = 2312; -- STT 136: ГК + ЛМ -A-90 1002
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1219 WHERE id = 2313; -- STT 137: ГК-NNK-A-90 1145
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1218 WHERE id = 2314; -- STT 138: ГК-NNK-A-90 1146
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1183 WHERE id = 2315; -- STT 139: ГК-NNK-A-90 1147
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1184 WHERE id = 2316; -- STT 140: ГК-NNK-A-90 1148
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 38 WHERE id = 2317; -- STT 141: БК-A-90 1007
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 39 WHERE id = 2318; -- STT 142: БК-A-90 1009

-- STT 143: LOẠI TRỪ (БК-A-90)

-- Record 144
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1181 WHERE id = 2320; -- STT 144: БК-A-90 1068

-- STT 145: LOẠI TRỪ (БК-A-90)

-- Records 146-160
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1357 WHERE id = 2322; -- STT 146: БК-A-90 1076
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 32 WHERE id = 2323; -- STT 147: ИK-A-90 1050
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 744 WHERE id = 2324; -- STT 148: ИK-A-90 1051
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1179 WHERE id = 2325; -- STT 149: ИK-A-90 1069
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1180 WHERE id = 2326; -- STT 150: ИK-A-90 1070
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 33 WHERE id = 2327; -- STT 151: ИФМ-А-90 959
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 34 WHERE id = 2328; -- STT 152: ИФМ-А-90 960
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1359 WHERE id = 2329; -- STT 153: ИФМ-А-90 1059
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1429 WHERE id = 2330; -- STT 154: ИФМ-А-90 1067
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 28 WHERE id = 2340; -- STT 155: AK-A-90 1018
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 37 WHERE id = 2341; -- STT 156: AK-A-90 1019
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 29 WHERE id = 2342; -- STT 157: AK-A-90 1077
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 850 WHERE id = 2343; -- STT 158: AK-A-90 1087
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1136 WHERE id = 2344; -- STT 159: AK-A-90 1091
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1358 WHERE id = 2345; -- STT 160: AK-A-90 1106

-- STT 161-165: LOẠI TRỪ (МТМ TP7V)

-- Records 166-173
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 576 WHERE id = 2353; -- STT 166: AЛM-76 1002
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 630 WHERE id = 2354; -- STT 167: AЛM-76 1003
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 40 WHERE id = 2355; -- STT 168: AЛM-76 1004
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 582 WHERE id = 2356; -- STT 169: AЛM-76 1011
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 528 WHERE id = 2357; -- STT 170: AЛM-76 1012
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 504 WHERE id = 2358; -- STT 171: AЛM-76 1013
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 577 WHERE id = 2359; -- STT 172: AЛM-76 1014
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 502 WHERE id = 2360; -- STT 173: AЛM-76 1015

-- STT 174: LOẠI TRỪ (AЛM-76)

-- Records 175-227
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1579 WHERE id = 2369; -- STT 175: CCL 2-3/4 JAEW8-02
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 172 WHERE id = 2044; -- STT 176: SDLT 11537128
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 114 WHERE id = 2060; -- STT 177: D2TS 95
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 115 WHERE id = 2061; -- STT 178: D2TS 98
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 85 WHERE id = 2082; -- STT 179: DLLT 2
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 87 WHERE id = 2083; -- STT 180: DLLT 5
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 190 WHERE id = 2098; -- STT 181: DTD 12633945
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 915 WHERE id = 2100; -- STT 182: Swivel- HDDS SWIVEL-12457113
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 544 WHERE id = 2101; -- STT 183: Swivel MCSA-D SWIVEL2 10932066
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 96 WHERE id = 2102; -- STT 184: DITS FLEX FLEX1
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 98 WHERE id = 2103; -- STT 185: DITS FLEX FLEX3
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 507 WHERE id = 2104; -- STT 186: DITS FLEX FLEX4
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 205 WHERE id = 2107; -- STT 187: CCL-IC 0717
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 206 WHERE id = 2108; -- STT 188: CCL-IC 0718
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 207 WHERE id = 2109; -- STT 189: CCL-IC 1036
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 208 WHERE id = 2110; -- STT 190: CCL-IC 1037
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 212 WHERE id = 2111; -- STT 191: NEC-IC 1203
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 209 WHERE id = 2112; -- STT 192: NEC-IC 0801
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 210 WHERE id = 2113; -- STT 193: NEC-IC 0802
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 235 WHERE id = 2114; -- STT 194: CNS - IC 1203
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 232 WHERE id = 2115; -- STT 195: CNS - IC 0720
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 233 WHERE id = 2116; -- STT 196: CNS - IC 0721
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 216 WHERE id = 2117; -- STT 197: REC-IC 1108
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 217 WHERE id = 2118; -- STT 198: REC-IC 1109
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 213 WHERE id = 2119; -- STT 199: REC-IC 0803
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 214 WHERE id = 2120; -- STT 200: REC-IC 0804
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 239 WHERE id = 2121; -- STT 201: BDS-IC 1201
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 240 WHERE id = 2122; -- STT 202: BDS-IC 1202
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 238 WHERE id = 2123; -- STT 203: BDS-IC 1002
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 236 WHERE id = 2124; -- STT 204: BDS-IC 0728
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 237 WHERE id = 2125; -- STT 205: BDS-IC 0729
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 202 WHERE id = 2126; -- STT 206: TGR-IC 1001
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 962 WHERE id = 2127; -- STT 207: TGR-IC 1117
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 795 WHERE id = 2128; -- STT 208: TGR-IC 1120
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 220 WHERE id = 2130; -- STT 209: TTR-IC 1004
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 221 WHERE id = 2131; -- STT 210: TTR-IC 1264
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 218 WHERE id = 2132; -- STT 211: TTR-IC 0726
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 219 WHERE id = 2133; -- STT 212: TTR-IC 0727
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 225 WHERE id = 2134; -- STT 213: DLS-IC 1002
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 226 WHERE id = 2135; -- STT 214: DLS-IC 1007
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 227 WHERE id = 2136; -- STT 215: DLS-IC 1008
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 223 WHERE id = 2137; -- STT 216: DLS-IC 0723
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 224 WHERE id = 2138; -- STT 217: DLS-IC 0724
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 247 WHERE id = 2145; -- STT 218: D4GC-IC 1203
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 248 WHERE id = 2146; -- STT 219: D4GC-IC 1204
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 245 WHERE id = 2147; -- STT 220: D4GC-IC 0725
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 246 WHERE id = 2148; -- STT 221: D4GC-IC 0726
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1299 WHERE id = 2151; -- STT 222: Flex sub FJS-IC 1005
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1300 WHERE id = 2152; -- STT 223: Flex sub FJS-IC 1006
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 253 WHERE id = 2153; -- STT 224: HAS-IC 1119
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 254 WHERE id = 2154; -- STT 225: HAS-IC 1120
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 251 WHERE id = 2155; -- STT 226: HAS-IC 0806
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 252 WHERE id = 2156; -- STT 227: HAS-IC 0812

-- STT 228: LOẠI TRỪ (VSP-Bộ nguồn nổ)

-- Records 229-242
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1059 WHERE id = 2171; -- STT 229: GRT No 32
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1055 WHERE id = 2172; -- STT 230: TAS No 90
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1056 WHERE id = 2173; -- STT 231: TAS No 91
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1057 WHERE id = 2174; -- STT 232: TAS TAS - 213
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1060 WHERE id = 2175; -- STT 233: VRS No: 71
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1061 WHERE id = 2176; -- STT 234: VRS No:73
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1048 WHERE id = 2177; -- STT 235: ASR No368
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1049 WHERE id = 2178; -- STT 236: ASR No369
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1050 WHERE id = 2179; -- STT 237: ASR No370
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1051 WHERE id = 2180; -- STT 238: ASR No371
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1052 WHERE id = 2181; -- STT 239: ASR No372
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1053 WHERE id = 2182; -- STT 240: ASR No373
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1054 WHERE id = 2183; -- STT 241: ASR No374
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 989 WHERE id = 2184; -- STT 242: ASR No375

-- STT 243: LOẠI TRỪ (VSP - Bộ định vị)

-- Records 244-248
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1645 WHERE id = 2187; -- STT 244: VSP-Máy nén khí đơn VSP001
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1653 WHERE id = 2188; -- STT 245: VSP-Máy nén khí đơn VSP002
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1161 WHERE id = 2201; -- STT 246: iDIU 5
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1162 WHERE id = 2202; -- STT 247: iDIL 5
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1173 WHERE id = 2211; -- STT 248: iDL 1

-- STT 249: LOẠI TRỪ (Centralizer TCME-BA)

-- Records 250-265
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 554 WHERE id = 2331; -- STT 250: AK-73 11
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 725 WHERE id = 2332; -- STT 251: AK-73 1023
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 617 WHERE id = 2333; -- STT 252: AK-73 1043
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 48 WHERE id = 2334; -- STT 253: AK-73 1044
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 726 WHERE id = 2335; -- STT 254: AK-73 1045
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 727 WHERE id = 2336; -- STT 255: AK-73 1046
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 555 WHERE id = 2337; -- STT 256: AK-73 1055
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 728 WHERE id = 2338; -- STT 257: AK-73 1057
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 729 WHERE id = 2339; -- STT 258: AK-73 1058
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 735 WHERE id = 2351; -- STT 259: ТД (TTVF) 2004
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 633 WHERE id = 2352; -- STT 260: ТД (TTVF) 2005
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 886 WHERE id = 2364; -- STT 261: CCL 3-1/8 J5ID3-05
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 888 WHERE id = 2365; -- STT 262: CCL 3-1/8 J5ID3-06
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 887 WHERE id = 2367; -- STT 263: CCL 3-1/8 J5ID3-11
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1569 WHERE id = 2197; -- STT 264: Swivel Signum 5
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET thietbi_id = 1171 WHERE id = 2208; -- STT 265: Cable head Sig 4

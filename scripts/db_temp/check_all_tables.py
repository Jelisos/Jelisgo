import pymysql

def main():
    conn = pymysql.connect(
        host='localhost',
        port=3306,
        user='root',
        password='',
        database='wallpaper_db',
        charset='utf8mb4'
    )
    cursor = conn.cursor()
    try:
        # 获取所有表名
        print("\n=== 数据库中的所有表 ===")
        cursor.execute("SHOW TABLES;")
        tables = cursor.fetchall()
        for table in tables:
            table_name = table[0]
            print(f"\n=== {table_name} 表结构 ===")
            cursor.execute(f"DESCRIBE {table_name};")
            columns = cursor.fetchall()
            for col in columns:
                print(col)
            
    except Exception as e:
        print(f"错误: {e}")
    finally:
        cursor.close()
        conn.close()

if __name__ == "__main__":
    main()
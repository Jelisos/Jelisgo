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
        print("\n=== wallpaper_likes 表结构 ===")
        cursor.execute("DESCRIBE wallpaper_likes;")
        columns = cursor.fetchall()
        for col in columns:
            print(col)
            
        print("\n=== wallpaper_views 表结构 ===")
        cursor.execute("DESCRIBE wallpaper_views;")
        columns_views = cursor.fetchall()
        for col in columns_views:
            print(col)

        print("\n=== wallpaper_exile_status 表结构 ===")
        cursor.execute("DESCRIBE wallpaper_exile_status;")
        columns_exile = cursor.fetchall()
        for col in columns_exile:
            print(col)

        print("\n=== wallpaper_operation_log 表结构 ===")
        cursor.execute("DESCRIBE wallpaper_operation_log;")
        columns_log = cursor.fetchall()
        for col in columns_log:
            print(col)
            
    except Exception as e:
        print(f"错误: {e}")
    finally:
        cursor.close()
        conn.close()

if __name__ == "__main__":
    main()
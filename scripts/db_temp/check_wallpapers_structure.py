import mysql.connector

try:
    conn = mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='wallpaper_db'
    )
    cursor = conn.cursor()
    
    # 查看wallpapers表结构
    cursor.execute('DESCRIBE wallpapers')
    columns = cursor.fetchall()
    
    print('wallpapers表结构:')
    for col in columns:
        print(f'{col[0]} - {col[1]} - {col[2]} - {col[3]}')
    
    # 查看前几条记录
    cursor.execute('SELECT * FROM wallpapers LIMIT 3')
    rows = cursor.fetchall()
    
    print('\n前3条记录:')
    for row in rows:
        print(row)
    
    conn.close()
    
except Exception as e:
    print(f'错误: {e}')
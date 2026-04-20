import mysql.connector
import os
import shutil
from datetime import date

# 1. Database Configuration
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'epwts_db'
}

# 2. Path Configuration
UPLOAD_DIR = "../uploads/"
ARCHIVE_DIR = "../archived/"

# Ensure archive directory exists
if not os.path.exists(ARCHIVE_DIR):
    os.makedirs(ARCHIVE_DIR)

def archive_expired_documents():
    try:
        # Connect to MySQL
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor(dictionary=True)

        # Query for documents where Expiry Date is today or earlier
        today = date.today().strftime('%Y-%m-%d')
        query = "SELECT id, doc_id, file_name, file_path FROM documents WHERE expiry_date <= %s"
        cursor.execute(query, (today,))
        
        expired_docs = cursor.fetchall()

        if not expired_docs:
            print(f"[{today}] No expired documents found.")
            return

        for doc in expired_docs:
            file_name = doc['file_name']
            old_path = os.path.join(UPLOAD_DIR, file_name)
            new_path = os.path.join(ARCHIVE_DIR, file_name)

            # Move the physical file
            if os.path.exists(old_path):
                shutil.move(old_path, new_path)
                print(f"Archived: {doc['doc_id']} - {file_name}")
                
                # Optional: Update database status or delete record
                # cursor.execute("DELETE FROM documents WHERE id = %s", (doc['id'],))
            else:
                print(f"File not found for: {doc['doc_id']}")

        conn.commit()
        cursor.close()
        conn.close()

    except mysql.connector.Error as err:
        print(f"Error: {err}")

if __name__ == "__main__":
    archive_expired_documents()

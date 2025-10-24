import urllib.parse

password = urllib.parse.quote_plus("_P@ssw0rdEX!T0")

DB_CONNECTION_STRING = (
    f"mssql+pyodbc://SisIntranet:{password}@172.20.1.248/DB_PROC"
    "?driver=ODBC+Driver+17+for+SQL+Server"
    "&TrustServerCertificate=yes"
    "&Max+Pool+Size=600"
)
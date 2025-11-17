using System;
using System.Collections.Generic;
using System.Data;
using Microsoft.Data.SqlClient;
using System.Text;

namespace PipelineMLRoveri.DAO
{
    public class DAL_PROC_ROVERI
    {
        string strConexao;
        SqlConnection SQLConexao;
        static DAL_PROC_ROVERI instancia;
          
        public DAL_PROC_ROVERI()
        {
            /* BASE DESENVOLVIMENTO */
            strConexao = "Server=172.20.1.248; Database=DB_PROC_ROVERI;  User Id=sa; Password=t!gt3@br@sil;TrustServerCertificate=True;Connection Timeout=540";
            SQLConexao = new SqlConnection(strConexao);
        }

        public static DAL_PROC_ROVERI Instancia
        {
            get
            {
                if (instancia == null)
                    instancia = new DAL_PROC_ROVERI();
                return instancia;
            }
        }

        private void ConectaDataBase()
        {
            SQLConexao.Open();
        }

        private void DesconectaDataBase()
        {
            SQLConexao.Close();
        }

        public int ExecutaComandoSQL(SqlCommand sqlCommand)
        {
            try
            {
                int LinhasAfetadas = 0;

                ConectaDataBase();
                SqlTransaction sqltran = SQLConexao.BeginTransaction();

                try
                {
                    sqlCommand.Transaction = sqltran;
                    sqlCommand.CommandTimeout = 0;
                    sqlCommand.Connection = SQLConexao;
                    LinhasAfetadas = sqlCommand.ExecuteNonQuery();

                    sqltran.Commit();
                    DesconectaDataBase();
                    return LinhasAfetadas;
                }
                catch (Exception ex)
                {
                    sqltran.Rollback();
                    DesconectaDataBase();
                    throw new Exception("DAL_PROC_ROVERI_001: " + ex.Message, ex);
                }
            }
            catch (Exception ex)
            {
                throw new Exception("DAL_PROC_ROVERI_001: " + ex.Message, ex);
            }
        }


        public int ExecutaComandoSQL_NoTrans(SqlCommand sqlCommand)
        {
            int LinhasAfetadas = 0;
            ConectaDataBase();

            try
            {
                sqlCommand.CommandTimeout = 0;
                sqlCommand.Connection = SQLConexao;
                LinhasAfetadas = sqlCommand.ExecuteNonQuery();

                DesconectaDataBase();
                return LinhasAfetadas;
            }
            catch (Exception ex)
            {
                DesconectaDataBase();
                throw new Exception("DAL_PROC_ROVERI_002: " + ex.Message, ex);
            }
        }


        public int ExecutaComandoSQL_IDENT(SqlCommand sqlCommand)
        {
            int LinhasAfetadas = 0;

            ConectaDataBase();
            SqlTransaction sqltran = SQLConexao.BeginTransaction();

            SqlCommand sqlComm = new SqlCommand("SET QUOTED_IDENTIFIER OFF", SQLConexao);
            try
            {
                sqlCommand.Transaction = sqltran;
                sqlComm.Transaction = sqltran;

                sqlCommand.CommandTimeout = 0;
                sqlCommand.Connection = SQLConexao;

                sqlComm.ExecuteNonQuery();
                LinhasAfetadas = sqlCommand.ExecuteNonQuery();

                sqltran.Commit();
                DesconectaDataBase();
                return LinhasAfetadas;
            }
            catch (Exception ex)
            {
                sqltran.Rollback();
                DesconectaDataBase();
                throw new Exception("DAL_PROC_ROVERI_003: " + ex.Message, ex);
            }
        }

        public DataSet ConsultaSQL(SqlCommand sqlCommand)
        {
            DataSet ds = new DataSet();
            ConectaDataBase();
            SqlTransaction sqltran = SQLConexao.BeginTransaction();

            try
            {
                sqlCommand.Connection = SQLConexao;
                sqlCommand.CommandTimeout = 0;
                sqlCommand.Transaction = sqltran;

                SqlDataAdapter da = new SqlDataAdapter();
                da.SelectCommand = sqlCommand;
                da.Fill(ds);

                sqltran.Commit();
                DesconectaDataBase();
                return ds;
            }
            catch (Exception ex)
            {
                sqltran.Rollback();
                DesconectaDataBase();
                throw new Exception("DAL_PROC_ROVERI_004: " + ex.Message, ex);
            }
        }

        public int ExecutaBulkCopySQL(DataTable TabelaCarga, string TabelaDestino)
        {
            ConectaDataBase();
            try
            {
                SqlBulkCopy sqlCopy = new SqlBulkCopy(SQLConexao);
                sqlCopy.BulkCopyTimeout = 0;
                sqlCopy.DestinationTableName = TabelaDestino;
                sqlCopy.WriteToServer(TabelaCarga);

                DesconectaDataBase();
                return (1);
            }
            catch (Exception ex)
            {
                DesconectaDataBase();
                throw new Exception("DAL_PROC_ROVERI_005: " + ex.Message, ex);
            }
        }

        public int ExecutaBulkCopySQL(DataRow[] TabelaCarga, string TabelaDestino)
        {
            ConectaDataBase();
            try
            {
                SqlBulkCopy sqlCopy = new SqlBulkCopy(SQLConexao);
                sqlCopy.BulkCopyTimeout = 0;
                sqlCopy.DestinationTableName = TabelaDestino;
                sqlCopy.WriteToServer(TabelaCarga);

                DesconectaDataBase();
                return (1);
            }
            catch (Exception ex)
            {
                DesconectaDataBase();
                throw new Exception("DAL_PROC_ROVERI_006: " + ex.Message, ex);
            }
        }
    }
}

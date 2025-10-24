using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Configuration;
using System.IO;


namespace RvMoni.Repositories
{
    public class DbProcContextFactory
    {
        public static DbProcContext Create()
        {
            var config = new ConfigurationBuilder().Build();

            var connectionString = config.GetConnectionString("DbProc")
                ?? "Server=172.20.1.248;Database=DB_PROC;User Id=SisIntranet;Password=_P@ssw0rdEX!T0;TrustServerCertificate=True;";

            var optionsBuilder = new DbContextOptionsBuilder<DbProcContext>();
            optionsBuilder.UseSqlServer(connectionString);

            return new DbProcContext(optionsBuilder.Options);
        }
    }

}

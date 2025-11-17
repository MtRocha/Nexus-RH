using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Telegram.Bot;
using System.IO;
using System.Net;

namespace CamadaDados
{
    public class TELEGRAM
    {

        private static readonly TelegramBotClient Bot = new TelegramBotClient("911289256:AAHeEF-iJPzlF9Vl2OdjY7HXdA8Bcnm7Kxg");

        public void SendTelegram(string mensagem)
        {
            try
            {

                string urlString = "https://api.telegram.org/bot{0}/sendMessage?chat_id={1}&text={2}&parse_mode=html";
                string apiToken = "911289256:AAHeEF-iJPzlF9Vl2OdjY7HXdA8Bcnm7Kxg";
                string chatId = "-395928385";
                string text = mensagem;
                urlString = String.Format(urlString, apiToken, chatId, text);
                WebRequest request = WebRequest.Create(urlString);
                Stream rs = request.GetResponse().GetResponseStream();
                StreamReader reader = new StreamReader(rs);
                string line = "";
                StringBuilder sb = new StringBuilder();
                while (line != null)
                {
                    line = reader.ReadLine();
                    if (line != null)
                        sb.Append(line);
                }
                string response = sb.ToString();

            }

            catch //(Exception ex)
            {
                //throw (new Exception(ex.Message, ex));
            }
        }

        public void SendTelegramBilhete(string mensagem)
        {
            try
            {
                string urlString = "https://api.telegram.org/bot{0}/sendMessage?chat_id={1}&text={2}&parse_mode=html";
                string apiToken = "1138253656:AAFTyyoXIAScHH1eKYmb_zaa7nYdWeie0GU";
                string chatId = "-409767586";
                string text = mensagem;
                urlString = String.Format(urlString, apiToken, chatId, text);
                WebRequest request = WebRequest.Create(urlString);
                Stream rs = request.GetResponse().GetResponseStream();
                StreamReader reader = new StreamReader(rs);
                string line = "";
                StringBuilder sb = new StringBuilder();
                while (line != null)
                {
                    line = reader.ReadLine();
                    if (line != null)
                        sb.Append(line);
                }
                string response = sb.ToString();

            }

            catch //(Exception ex)
            {
                //throw (new Exception(ex.Message, ex));
            }
        }

        public void SendTelegramAna(string mensagem)
        {
            try
            {
                string urlString = "https://api.telegram.org/bot{0}/sendMessage?chat_id={1}&text={2}&parse_mode=html";
                string apiToken = "1133022503:AAGzVK7JRza2zfTsgu0z-F3m33Zam0rIzBI";
                string chatId = "-447907604";
                string text = mensagem;
                urlString = String.Format(urlString, apiToken, chatId, text);
                WebRequest request = WebRequest.Create(urlString);
                Stream rs = request.GetResponse().GetResponseStream();
                StreamReader reader = new StreamReader(rs);
                string line = "";
                StringBuilder sb = new StringBuilder();
                while (line != null)
                {
                    line = reader.ReadLine();
                    if (line != null)
                        sb.Append(line);
                }
                string response = sb.ToString();
            }

            catch //(Exception ex)
            {
                //throw (new Exception(ex.Message, ex));
            }
        }

        public void SendTelegramoOperacao(string mensagem)
        {
            try
            {
                string urlString = "https://api.telegram.org/bot{0}/sendMessage?chat_id={1}&text={2}&parse_mode=html";
                string apiToken = "1352962945:AAFOkMPWQ5M2xuGOJ-q-f_YoxNybd22ezJQ";
                string chatId = "-423765700";
                string text = mensagem;
                urlString = String.Format(urlString, apiToken, chatId, text);
                WebRequest request = WebRequest.Create(urlString);
                Stream rs = request.GetResponse().GetResponseStream();
                StreamReader reader = new StreamReader(rs);
                string line = "";
                StringBuilder sb = new StringBuilder();
                while (line != null)
                {
                    line = reader.ReadLine();
                    if (line != null)
                        sb.Append(line);
                }
                string response = sb.ToString();
            }

            catch//(Exception ex)
            {
                //throw (new Exception(ex.Message, ex));
            }
        }

        public void SendTelegramoOperacaoTele(string mensagem)
        {
            try
            {
                string urlString = "https://api.telegram.org/bot{0}/sendMessage?chat_id={1}&text={2}&parse_mode=html";
                string apiToken = "1352962945:AAFOkMPWQ5M2xuGOJ-q-f_YoxNybd22ezJQ";
                string chatId = "-461066980";
                string text = mensagem;
                urlString = String.Format(urlString, apiToken, chatId, text);
                WebRequest request = WebRequest.Create(urlString);
                Stream rs = request.GetResponse().GetResponseStream();
                StreamReader reader = new StreamReader(rs);
                string line = "";
                StringBuilder sb = new StringBuilder();
                while (line != null)
                {
                    line = reader.ReadLine();
                    if (line != null)
                        sb.Append(line);
                }
                string response = sb.ToString();
            }

            catch //(Exception ex)
            {
                //throw (new Exception(ex.Message, ex));
            }
        }

        public void SendTelegramCargaSoudi(string mensagem)
        {
            try
            {
                string urlString = "https://api.telegram.org/bot{0}/sendMessage?chat_id={1}&text={2}&parse_mode=html";
                string apiToken = "1405112791:AAFYn3vfCE1Z5N2D8TGXiKD-OfPNvcbDZrk";
                string chatId = "-459672415";
                string text = mensagem;
                urlString = String.Format(urlString, apiToken, chatId, text);
                WebRequest request = WebRequest.Create(urlString);
                Stream rs = request.GetResponse().GetResponseStream();
                StreamReader reader = new StreamReader(rs);
                string line = "";
                StringBuilder sb = new StringBuilder();
                while (line != null)
                {
                    line = reader.ReadLine();
                    if (line != null)
                        sb.Append(line);
                }
                string response = sb.ToString();
            }

            catch //(Exception ex)
            {
                //throw (new Exception(ex.Message, ex));
            }
        }

        public void SendTelegramDigital(string mensagem)
        {
            try
            {
                string urlString = "https://api.telegram.org/bot{0}/sendMessage?chat_id={1}&text={2}&parse_mode=html";
                string apiToken = "1801718814:AAHHbIKGlrFEhmMp22GzkgvM5tLjCvC7HEM";
                string chatId = "-516387501";
                string text = mensagem;
                urlString = String.Format(urlString, apiToken, chatId, text);
                WebRequest request = WebRequest.Create(urlString);
                Stream rs = request.GetResponse().GetResponseStream();
                StreamReader reader = new StreamReader(rs);
                string line = "";
                StringBuilder sb = new StringBuilder();
                while (line != null)
                {
                    line = reader.ReadLine();
                    if (line != null)
                        sb.Append(line);
                }
                string response = sb.ToString();
            }

            catch//(Exception ex)
            {
                //throw (new Exception(ex.Message, ex));
            }
        }
    }
}

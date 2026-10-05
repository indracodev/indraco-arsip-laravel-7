using System;
using System.Diagnostics;
using System.IO;
using System.Reflection;

[assembly: AssemblyTitle("PT INDRACO - Document Management System (DMS) Launcher")]
[assembly: AssemblyDescription("Desktop Auto-Launcher for PT Indraco Document Management System")]
[assembly: AssemblyCompany("PT INDRACO")]
[assembly: AssemblyProduct("PT INDRACO DMS Desktop Edition")]
[assembly: AssemblyCopyright("Copyright © PT INDRACO 2026")]
[assembly: AssemblyVersion("1.0.0.0")]
[assembly: AssemblyFileVersion("1.0.0.0")]

namespace IndracoDms
{
    class Program
    {
        static int Main(string[] args)
        {
            string baseDir = AppDomain.CurrentDomain.BaseDirectory;
            Environment.CurrentDirectory = baseDir;

            string batPath = Path.Combine(baseDir, "START-DMS-INDRACO.bat");
            if (!File.Exists(batPath))
            {
                Console.ForegroundColor = ConsoleColor.Red;
                Console.WriteLine("===============================================================================");
                Console.WriteLine("[ERROR] File START-DMS-INDRACO.bat tidak ditemukan!");
                Console.WriteLine("Pastikan file launcher .exe berada di direktori root aplikasi:");
                Console.WriteLine(baseDir);
                Console.WriteLine("===============================================================================");
                Console.ResetColor();
                Console.WriteLine("\nTekan Enter untuk keluar...");
                Console.ReadLine();
                return 1;
            }

            ProcessStartInfo psi = new ProcessStartInfo
            {
                FileName = "cmd.exe",
                Arguments = "/c \"\"" + batPath + "\"\"",
                WorkingDirectory = baseDir,
                UseShellExecute = false
            };

            try
            {
                using (Process proc = Process.Start(psi))
                {
                    proc.WaitForExit();
                    return proc.ExitCode;
                }
            }
            catch (Exception ex)
            {
                Console.ForegroundColor = ConsoleColor.Red;
                Console.WriteLine("===============================================================================");
                Console.WriteLine("[ERROR] Gagal menjalankan launcher: " + ex.Message);
                Console.WriteLine("===============================================================================");
                Console.ResetColor();
                Console.WriteLine("\nTekan Enter untuk keluar...");
                Console.ReadLine();
                return 1;
            }
        }
    }
}

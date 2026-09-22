Set WshShell = CreateObject("WScript.Shell")
WshShell.CurrentDirectory = CreateObject("Scripting.FileSystemObject").GetParentFolderName(WScript.ScriptFullName)

' Jalankan php artisan serve secara silent (tanpa pop up jendela hitam CMD)
WshShell.Run "cmd /c php artisan serve --host=0.0.0.0 --port=8000", 0, False

' Tunggu 2 detik agar server siap menerima request
WScript.Sleep 2000

' Buka web browser default ke sistem DMS Indraco
WshShell.Run "http://127.0.0.1:8000"

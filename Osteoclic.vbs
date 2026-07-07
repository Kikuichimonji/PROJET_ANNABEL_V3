' Lance Osteoclic sans fenetre console visible, puis ouvre le navigateur.
' Ne depend plus de Laragon : utilise directement le serveur PHP integre.

Option Explicit

Dim fso, shell, projectDir, publicDir, pidFile, q
Set fso = CreateObject("Scripting.FileSystemObject")
Set shell = CreateObject("WScript.Shell")
q = Chr(34)

projectDir = fso.GetParentFolderName(WScript.ScriptFullName)
publicDir = projectDir & "\public"
pidFile = projectDir & "\var\osteoclic.pid"

Const HOST = "127.0.0.1"
Const PORT = "8000"
Dim url : url = "http://" & HOST & ":" & PORT & "/"

' --- Si un serveur tourne deja (fichier pid present et process vivant), on ouvre juste le navigateur ---
If fso.FileExists(pidFile) Then
    Dim existingPid, isAlive
    isAlive = False
    On Error Resume Next
    existingPid = Trim(fso.OpenTextFile(pidFile, 1).ReadLine())
    On Error Goto 0
    If existingPid <> "" And IsNumeric(existingPid) Then
        Dim p
        For Each p In GetObject("winmgmts:\\.\root\cimv2").ExecQuery("SELECT ProcessId FROM Win32_Process WHERE ProcessId = " & existingPid)
            isAlive = True
        Next
    End If
    If isAlive Then
        shell.Run url
        WScript.Quit 0
    End If
End If

' --- Localisation de php.exe (PATH, sinon installation Laragon par defaut) ---
Dim phpExe : phpExe = ""

On Error Resume Next
Dim whereResult : whereResult = shell.Exec("where php").StdOut.ReadAll()
On Error Goto 0
If InStr(whereResult, "php.exe") > 0 Then
    phpExe = "php"
End If

If phpExe = "" And fso.FolderExists("C:\laragon\bin\php") Then
    Dim folder
    For Each folder In fso.GetFolder("C:\laragon\bin\php").SubFolders
        If fso.FileExists(folder.Path & "\php.exe") Then
            phpExe = folder.Path & "\php.exe"
        End If
    Next
End If

If phpExe = "" Then
    MsgBox "Impossible de trouver php.exe (ni dans le PATH, ni dans C:\laragon\bin\php)." & vbCrLf & _
           "Installez/reparez Laragon, ou ajoutez PHP au PATH Windows.", vbCritical, "Osteoclic"
    WScript.Quit 1
End If

' --- Lancement du serveur PHP integre, cache, en recuperant son PID ---
Dim command : command = q & phpExe & q & " -S " & HOST & ":" & PORT & " -t " & q & publicDir & q

Dim wmi, startup, processObj, newPid
Set wmi = GetObject("winmgmts:\\.\root\cimv2")
Set startup = wmi.Get("Win32_ProcessStartup").SpawnInstance_()
startup.ShowWindow = 0
Set processObj = wmi.Get("Win32_Process")
newPid = 0
processObj.Create command, projectDir, startup, newPid

If newPid = 0 Then
    MsgBox "Le serveur PHP n'a pas pu demarrer.", vbCritical, "Osteoclic"
    WScript.Quit 1
End If

If Not fso.FolderExists(projectDir & "\var") Then
    fso.CreateFolder projectDir & "\var"
End If
Dim pidStream : Set pidStream = fso.CreateTextFile(pidFile, True)
pidStream.WriteLine newPid
pidStream.Close

' --- Attente que le serveur reponde, puis ouverture du navigateur ---
Dim attempts, ready, http
ready = False
For attempts = 1 To 20
    WScript.Sleep 300
    On Error Resume Next
    Set http = CreateObject("MSXML2.XMLHTTP")
    http.Open "GET", url, False
    http.Send
    If Err.Number = 0 Then
        ready = True
    End If
    On Error Goto 0
    If ready Then Exit For
Next

shell.Run url

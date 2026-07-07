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

' --- Localisation de php.exe ---
' Ce projet (Symfony 5.1 / Doctrine ORM 2.7) requiert PHP 7.x : PHP 8.3 (Laragon)
' fait planter Doctrine. On privilegie donc l'installation PHP 7.4 dediee.
Dim phpExe : phpExe = ""

If fso.FileExists("C:\tools\php74\php.exe") Then
    phpExe = "C:\tools\php74\php.exe"
End If

If phpExe = "" Then
    On Error Resume Next
    Dim whereResult : whereResult = shell.Exec("where php").StdOut.ReadAll()
    On Error Goto 0
    If InStr(whereResult, "php.exe") > 0 Then
        phpExe = "php"
    End If
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
    MsgBox "Impossible de trouver php.exe (ni C:\tools\php74, ni le PATH, ni C:\laragon\bin\php)." & vbCrLf & _
           "Installez PHP 7.4, ou ajoutez PHP au PATH Windows.", vbCritical, "Osteoclic"
    WScript.Quit 1
End If

' --- Lancement du serveur PHP integre, cache (WshShell.Run, methode standard) ---
Dim command : command = q & phpExe & q & " -S " & HOST & ":" & PORT & " -t " & q & publicDir & q
shell.CurrentDirectory = projectDir
shell.Run command, 0, False

' --- Recuperation du PID en cherchant le process php.exe qui sert notre dossier public ---
' (plus fiable que le parametre ByRef de Win32_Process.Create, qui peut echouer silencieusement)
Dim wmi, newPid, pidAttempts, processes, proc
Set wmi = GetObject("winmgmts:\\.\root\cimv2")
newPid = 0
For pidAttempts = 1 To 15
    WScript.Sleep 300
    Set processes = wmi.ExecQuery("SELECT ProcessId, CommandLine FROM Win32_Process WHERE Name='php.exe'")
    For Each proc In processes
        If Not IsNull(proc.CommandLine) Then
            If InStr(proc.CommandLine, publicDir) > 0 Then
                newPid = proc.ProcessId
            End If
        End If
    Next
    If newPid <> 0 Then Exit For
Next

If newPid = 0 Then
    MsgBox "Le serveur PHP n'a pas pu demarrer (introuvable apres lancement)." & vbCrLf & _
           "Commande tentee : " & command, vbCritical, "Osteoclic"
    WScript.Quit 1
End If

If Not fso.FolderExists(projectDir & "\var") Then
    fso.CreateFolder projectDir & "\var"
End If
Dim pidStream : Set pidStream = fso.CreateTextFile(pidFile, True)
pidStream.WriteLine CStr(newPid)
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

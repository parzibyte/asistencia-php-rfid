/*
  ____          _____               _ _           _       
 |  _ \        |  __ \             (_) |         | |      
 | |_) |_   _  | |__) |_ _ _ __ _____| |__  _   _| |_ ___ 
 |  _ <| | | | |  ___/ _` | '__|_  / | '_ \| | | | __/ _ \
 | |_) | |_| | | |  | (_| | |   / /| | |_) | |_| | ||  __/
 |____/ \__, | |_|   \__,_|_|  /___|_|_.__/ \__, |\__\___|
         __/ |                               __/ |        
        |___/                               |___/         
    
____________________________________
/ Si necesitas ayuda, contáctame en \
\ https://parzibyte.me               /
 ------------------------------------
        \   ^__^
         \  (oo)\_______
            (__)\       )\/\
                ||----w |
                ||     ||
Creado por Parzibyte (https://parzibyte.me).
------------------------------------------------------------------------------------------------
Si el código es útil para ti, puedes agradecerme siguiéndome: https://parzibyte.me/blog/sigueme/
Y compartiendo mi blog con tus amigos
También tengo canal de YouTube: https://www.youtube.com/channel/UCroP4BTWjfM0CkGB6AFUoBg?sub_confirmation=1
------------------------------------------------------------------------------------------------
*/
#include <Arduino.h>
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>

/*
In the ESP8266, D3 pin is RST_PIN and
D4 pin is SS_PIN
*/
#define RST_PIN D3
#define SS_PIN D4

MFRC522 reader(SS_PIN, RST_PIN);
MFRC522::MIFARE_Key key;

// Credentials to connect to the wifi network
const char *ssid = "SSID";
const char *password = "PASSWORD";
/*
The ip or server address. If you are on localhost, put your computer's IP (for example http://192.168.1.65)
If the server is online, put the server's domain for example https://parzibyte.me
*/
const String SERVER_ADDRESS = "http://192.168.1.77/asistencia-php-rfid";
void setup()
{

  // Connect to wifi
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED)
  {
    delay(1000);
  }

  SPI.begin();

  reader.PCD_Init();
  // Just wait some seconds...
  delay(4);
  // Prepare the security key for the read and write functions.
  // Normally it is 0xFFFFFFFFFFFF
  // Note: 6 comes from MF_KEY_SIZE in MFRC522.h
  for (byte i = 0; i < 6; i++)
  {
    key.keyByte[i] = 0xFF; //keyByte is defined in the "MIFARE_Key" 'struct' definition in the .h file of the library
  }
}

void loop()
{
  // If not connected, we don't need to read anything, that would be unnecessary
  if (WiFi.status() != WL_CONNECTED)
  {
    return;
  }
  // But, if there is a connection we check if there's a new card to read

  // Reset the loop if no new card present on the sensor/reader. This saves the entire process when idle.
  if (!reader.PICC_IsNewCardPresent())
  {
    return;
  }

  // Select one of the cards. This returns false if read is not successful; and if that happens, we stop the code
  if (!reader.PICC_ReadCardSerial())
  {
    return;
  }

  /*
    At this point we are sure that: there is a card that can be read, and there's a
    stable connection. So we read the id and send it to the server
  */

  String serial = "";
  for (int x = 0; x < reader.uid.size; x++)
  {
    // If it is less than 10, we add zero
    if (reader.uid.uidByte[x] < 0x10)
    {
      serial += "0";
    }
    // Transform the byte to hex
    serial += String(reader.uid.uidByte[x], HEX);
    // Add a hypen
    if (x + 1 != reader.uid.size)
    {
      serial += "-";
    }
  }
  // Transform to uppercase
  serial.toUpperCase();

  // Halt PICC
  reader.PICC_HaltA();
  // Stop encryption on PCD
  reader.PCD_StopCrypto1();

  HTTPClient http;

  // Send the tag id in a GET param
  const String full_url = SERVER_ADDRESS + "/rfid_register.php?serial=" + serial;
  http.begin(full_url);

  // Make request
  int httpCode = http.GET();
  if (httpCode > 0)
  {
    if (httpCode == HTTP_CODE_OK)
    {

      // const String &payload = http.getString().c_str(); //Get the request response payload
    }
    else
    {
    }
  }
  else
  {
  }

  http.end(); //Close connection
}
